<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\ForwardCompatibility\Result;
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

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        EshopCoreConfig $config
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
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

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \Exception
     */
    public function getPayPalOrderIdByShopOrderId(string $shopOrderId): string
    {
        $id = '';
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxorderid' => $shopOrderId
        ];

        $queryBuilder->select('oxpaypalorderid')
            ->from('oscpaypal_order')
            ->where('oxorderid = :oxorderid');

        $result = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute();

        if($result instanceof Result ) {
            $id = $result->fetchOne();
            if ($id !== '' && is_string($id)) {
                return $id;
            }
        }

        throw new \Exception('Order ID `'.$shopOrderId.'` not found.');
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public function cleanUpNotFinishedOrders(): void
    {
        if (!$this->config->getConfigParam('oscPayPalCleanUpNotFinishedOrdersAutomaticlly')) {
            return;
        }

        $sessiontime = $this->config->getConfigParam('oscPayPalStartTimeCleanUpOrders');
        if (is_int($sessiontime)){
            $sessiontime = (int)$sessiontime;
        }
        $shopId = $this->config->getShopId();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxordernr' => '0',
            'oxtransstatus' => 'NOT_FINISHED',
            'oxpaymenttype' => 'oscpaypal',
            'sessiontime' => $sessiontime,
            'oxshopid' => $shopId
        ];

        $queryBuilder->select('oxid')
            ->from('oxorder')
            ->where('oxordernr = :oxordernr')
            ->andWhere('oxtransstatus = :oxtransstatus')
            ->andWhere('oxshopid = :oxshopid')
            ->andWhere($queryBuilder->expr()->like(
                'oxpaymenttype',
                $queryBuilder->expr()->literal('%' . $parameters['oxpaymenttype'] . '%')
            ))
            ->andWhere('oxorderdate < now() - interval :sessiontime MINUTE');

        $result = $queryBuilder->setParameters($parameters)
            ->execute();

        if($result instanceof Result ) {
            $ids = $result->fetchOne();
            if (is_array($ids)) {
                foreach ($ids as $id) {
                    $order = oxNew(EshopModelOrder::class);
                    if ($order->load($id)) {
                        // storno
                        $order->cancelOrder();
                        // delete
                        $order->delete();
                    }
                }
            }
        }
    }

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \Exception
     */
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

        $result = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute();

        if($result instanceof Result ) {
            $id = $result->fetchOne();
            if ($id !== '' && is_string($id)) {
                return $id;
            }
        }

        throw new \Exception('Order not found.');
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     * @throws \Exception
     */
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

        $result = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute();

        if($result instanceof Result ) {
            $id = $result->fetchOne();
            if ($id !== '' && is_string($id)) {
                return $id;
            }
        }

        throw new \Exception('Order not found.');
    }

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \Exception
     */
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

        $result = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute();

        if($result instanceof Result ) {
            $id = $result->fetchOne();
            if ($id !== '' && is_string($id)) {
                return $id;
            }
        }

        throw new \Exception('Order not found.');
    }
}
