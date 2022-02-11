<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use PDO;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidEsales\Eshop\Core\Config as EshopCoreConfig;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Core\Exception\NotFound;
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
        ContextInterface             $context,
        EshopCoreConfig              $config
    )
    {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->context = $context;
        $this->config = $config;
    }

    public function paypalOrderByOrderIdAndPayPalId(string $shopOrderId, string $paypalOrderId): PayPalOrderModel
    {
        $order = oxNew(PayPalOrderModel::class);
        $order->load($this->getId($shopOrderId, $paypalOrderId));

        if (!$order->isLoaded()) {
            $order->assign(
                [
                    'oxorderid' => $shopOrderId,
                    'oxpaypalorderid' => $paypalOrderId
                ]
            );
        }

        return $order;
    }

    /**
     * @throws NotFound
     */
    public function getShopOrderByPayPalOrderId(string $paypalOrderId): EshopModelOrder
    {
        $orderId = $this->getShopOrderId($paypalOrderId);
        if (empty($orderId)) {
            throw NotFound::orderNotFoundByPayPalId();
        }

        $order = oxNew(EshopModelOrder::class);
        $order->load($orderId);
        if (!$order->isLoaded()) {
            throw NotFound::orderNotFound();
        }

        return $order;
    }

    private function getId(string $shopOrderId, string $paypalOrderId): string
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxorderid' => $shopOrderId,
            'oxpaypalorderid' => $paypalOrderId
        ];

        $queryBuilder->select('oxid')
            ->from('osc_paypal_order')
            ->where('oxorderid = :oxorderid')
            ->andWhere('oxpaypalorderid = :oxpaypalorderid');

        $id = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return (string) $id;
    }

    private function getShopOrderId(string $paypalOrderId): string
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxpaypalorderid' => $paypalOrderId
        ];

        $queryBuilder->select('oxid')
            ->from('osc_paypal_order')
            ->where('oxpaypalorderid = :oxpaypalorderid')
            ->andWhere('LENGTH(oxorderid) > ');

        $id = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return (string) $id;
    }
}
