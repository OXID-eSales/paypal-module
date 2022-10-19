<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidSolutionCatalysts\PayPal\Core\Constants;
use PDO;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidEsales\Eshop\Core\Config as EshopCoreConfig;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;

class OrderRepository
{
    /** @var QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    /** @var ContextInterface */
    private $context;

    /** @var EshopCoreConfig */
    private $config;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ContextInterface $context,
        EshopCoreConfig $config
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->context = $context;
        $this->config = $config;
    }

    public function paypalOrderByOrderIdAndPayPalId(
        string $shopOrderId,
        string $paypalOrderId = '',
        string $payPalTransactionId = ''
    ): PayPalOrderModel {

        $oxid = $this->getId($shopOrderId, $paypalOrderId, $payPalTransactionId);
        //We might have a transactionid that is not yet saved to database, in that case we need
        //to search for empty transactionid
        $oxid = $oxid ?:
            (empty($payPalTransactionId) ? '' : $this->getId($shopOrderId, $paypalOrderId, ''));

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

    public function getPayPalOrderIdByShopOrderId(string $shopOrderId): string
    {
        /** @var QueryBuilder $queryBuilder */
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
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxordernr' => '0',
            'oxtransstatus' => 'NOT_FINISHED',
            'oxpaymenttype' => 'oscpaypal',
            'sessiontime' => Constants::PAYPAL_SESSION_TIMEOUT_IN_SEC
        ];

        $queryBuilder->select('oxid')
            ->from('oxorder')
            ->where('oxordernr = :oxordernr')
            ->andWhere('oxtransstatus = :oxtransstatus')
            ->andWhere($queryBuilder->expr()->like('oxpaymenttype', $queryBuilder->expr()->literal('%' . $parameters['oxpaymenttype'] . '%')))
            ->andWhere('oxorderdate < now() - interval :sessiontime SECOND');

        $ids = $queryBuilder->setParameters($parameters)
            ->execute()
            ->fetchAll(PDO::FETCH_COLUMN);

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

    private function getId(string $shopOrderId, string $paypalOrderId = '', $payPalTransactionId = ''): string
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxorderid' => $shopOrderId,
            'oscpaypaltransactionid' => $payPalTransactionId,
            'oscpaypaltransactiontype' => Constants::PAYPAL_TRANSACTION_TYPE_CAPTURE
        ];

        if ($paypalOrderId) {
            $parameters['oxpaypalorderid'] = $paypalOrderId;
        }

        $queryBuilder->select('oxid')
            ->from('oscpaypal_order')
            ->where('oxorderid = :oxorderid');

        if ($paypalOrderId) {
            $queryBuilder->andWhere('oxpaypalorderid = :oxpaypalorderid');
        }

        $queryBuilder->andWhere('oscpaypaltransactionid = :oscpaypaltransactionid');
        $queryBuilder->andWhere('oscpaypaltransactiontype = :oscpaypaltransactiontype');

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
}
