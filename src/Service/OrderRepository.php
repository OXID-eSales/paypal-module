<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use PDO;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidEsales\Eshop\Core\Config as EshopCoreConfig;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class OrderRepository
{
    /** @var QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    /** @var EshopCoreConfig */
    private $config;

    /** @var ModuleSettings */
    private $moduleSettingsService;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ModuleSettings $moduleSettingsService,
        EshopCoreConfig $config
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->moduleSettingsService = $moduleSettingsService;
        $this->config = $config;
    }

    public function paypalOrderByOrderIdAndPayPalId(
        string $shopOrderId,
        string $paypalOrderId = '',
        string $payPalTransactionId = ''
    ): PayPalOrderModel {

        $oxid = $this->getId(
            $shopOrderId,
            $paypalOrderId,
            $payPalTransactionId,
            Constants::PAYPAL_TRANSACTION_TYPE_CAPTURE
        );

        $order = oxNew(PayPalOrderModel::class);
        $order->load($oxid);

        if (!$order->isLoaded()) {
            // Do not create new tracking records with an empty shop order id.
            // This is the final persistence safeguard against orphaned rows.
            if (empty($shopOrderId)) {
                Registry::getLogger()->error(
                    'OrderRepository: refusing to create oscpaypal_order with empty shopOrderId',
                    ['paypalOrderId' => $paypalOrderId]
                );
                return $order;
            }

            $order->assign(
                [
                    'oxorderid' => $shopOrderId,
                    'oxpaypalorderid' => $paypalOrderId
                ]
            );
            $order->setTransactionId($payPalTransactionId);
        }

        return $order;
    }

    public function paypalOrderByOrderId(
        string $shopOrderId
    ): PayPalOrderModel {
        $result = null;

        $oxid = $this->getId($shopOrderId);
        $order = oxNew(PayPalOrderModel::class);
        $order->load($oxid);
        return $order;
    }

    /**
     * @throws NotFound
     */
    public function getShopOrderByPayPalOrderId(string $paypalOrderId): EshopModelOrder
    {
        $orderId = $this->getShopOrderIdByPaypalOrderId($paypalOrderId);
        if (empty($orderId)) {
            throw NotFound::orderNotFoundByPayPalOrderId();
        }

        $order = oxNew(EshopModelOrder::class);
        $order->load($orderId);
        if (!$order->isLoaded()) {
            throw NotFound::orderNotFound();
        }

        return $order;
    }

    /**
     * @throws NotFound
     */
    public function getShopOrderByPayPalTransactionId(string $paypalTransactionId): EshopModelOrder
    {
        $orderId = $this->getShopOrderIdByPaypalTransactionId($paypalTransactionId);
        if (empty($orderId)) {
            throw NotFound::orderNotFoundByPayPalTransactionId();
        }

        $order = oxNew(EshopModelOrder::class);
        $order->load($orderId);
        if (!$order->isLoaded()) {
            throw NotFound::orderNotFound();
        }

        return $order;
    }

    public function getPayPalOrderIdByShopOrderId(?string $shopOrderId = ''): string
    {
        if (!$shopOrderId) {
            return '';
        }

        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxorderid' => $shopOrderId
        ];

        $queryBuilder->select('oxpaypalorderid')
            ->from('oscpaypal_order')
            ->where('oxorderid = :oxorderid');

        $id = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return (string) $id;
    }

    public function cleanUpNotFinishedOrders(): void
    {
        if (!$this->moduleSettingsService->cleanUpNotFinishedOrdersAutomaticlly()) {
            return;
        }

        $sessiontime = $this->moduleSettingsService->getStartTimeCleanUpOrders();
        $shopId = $this->config->getShopId();

        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxtransstatus' => 'NOT_FINISHED',
            'oxpaymenttype' => 'oscpaypal',
            'sessiontime'   => $sessiontime,
            'oxshopid'      => $shopId,
            'oxstorno'      => '0',
            'pp_approved'   => 'APPROVED',
            'pp_completed'  => 'COMPLETED',
        ];

        // Skip orders that PayPal has already APPROVED or COMPLETED — those
        // represent a real payment-pending state where a webhook is expected
        // to finalize the order, and must not be cancelled by the cleanup
        // job. Orders whose oscpaypal_order row is missing or carries a non-
        // pending status (e.g. CREATED, customer never approved) are still
        // cleaned up as before. (0007946)
        $queryBuilder->select('oxorder.oxid')
            ->from('oxorder')
            ->leftJoin(
                'oxorder',
                'oscpaypal_order',
                'pp',
                'pp.oxorderid = oxorder.oxid'
            )
            ->where('oxorder.oxtransstatus = :oxtransstatus')
            ->andWhere('oxorder.oxshopid = :oxshopid')
            ->andWhere('oxorder.oxstorno = :oxstorno')
            ->andWhere($queryBuilder->expr()->like(
                'oxorder.oxpaymenttype',
                $queryBuilder->expr()->literal($parameters['oxpaymenttype'] . '%')
            ))
            ->andWhere('oxorder.oxorderdate < now() - interval :sessiontime MINUTE')
            ->andWhere(
                '(pp.oscpaypalstatus IS NULL '
                . 'OR pp.oscpaypalstatus NOT IN (:pp_approved, :pp_completed))'
            );

        $ids = $queryBuilder->setParameters($parameters)
            ->execute()
            ->fetchAllAssociative();

        foreach ($ids as $id) {
            $order = oxNew(EshopModelOrder::class);
            if ($order->load($id['oxid'])) {
                $order->cancelPayPalOrder();
            }
        }
    }

    private function getId(
        string $shopOrderId,
        string $paypalOrderId = '',
        string $payPalTransactionId = '',
        string $payPalTransactionType = ''
    ): string {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxorderid' => $shopOrderId,
        ];

        if ($paypalOrderId) {
            $parameters['oxpaypalorderid'] = $paypalOrderId;
        }
        if ($payPalTransactionId) {
            $parameters['oscpaypaltransactionid'] = $payPalTransactionId;
        }
        if ($payPalTransactionType) {
            $parameters['oscpaypaltransactiontype'] = $payPalTransactionType;
        }

        $queryBuilder->select('oxid')
            ->from('oscpaypal_order')
            ->where('oxorderid = :oxorderid');

        if ($paypalOrderId) {
            $queryBuilder->andWhere('oxpaypalorderid = :oxpaypalorderid');
        }

        if ($payPalTransactionId) {
            $queryBuilder->andWhere('oscpaypaltransactionid = :oscpaypaltransactionid');
        }

        if ($payPalTransactionType) {
            $queryBuilder->andWhere('oscpaypaltransactiontype = :oscpaypaltransactiontype');
        }

        $id = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return (string) $id;
    }

    private function getShopOrderIdByPaypalOrderId(string $paypalOrderId): string
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxpaypalorderid' => $paypalOrderId
        ];

        $queryBuilder->select('oxorderid')
            ->from('oscpaypal_order')
            ->where('oxpaypalorderid = :oxpaypalorderid')
            ->andWhere('LENGTH(oxorderid) > 0');

        $id = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return (string) $id;
    }

    private function getShopOrderIdByPaypalTransactionId(string $paypalTransactionId): string
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oscpaypaltransactionid' => $paypalTransactionId
        ];

        $queryBuilder->select('oxorderid')
            ->from('oscpaypal_order')
            ->where('oscpaypaltransactionid = :oscpaypaltransactionid')
            ->andWhere('LENGTH(oxorderid) > 0');

        $id = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return (string) $id;
    }
    /**
     * @return string
     */
    public function fetchCurrentShopOrderId(): string
    {
        return (string)Registry::getSession()->getVariable('sess_challenge');
    }
    public function fetchCurrentShopOrder(): Order
    {
        $shopOrderId = $this->fetchCurrentShopOrderId();
        $order = oxNew(Order::class);
        $order->load($shopOrderId);
        return $order;
    }
}
