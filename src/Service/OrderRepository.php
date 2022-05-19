<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;
use OxidEsales\Eshop\Core\Config as EshopCoreConfig;
use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\Database;

class OrderRepository
{
    /** @var DatabaseProvider */
    private $db;

    /** @var EshopCoreConfig */
    private $config;

    public function __construct(
        EshopCoreConfig $config,
        Database $db
    ) {
        $this->config = $config;
        $this->db = $db;
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
        $query = "select oxid from oscpaypal_order where oxorderid = :oxorderid and oxpaypalorderid = :oxpaypalorderid";
        $id = $this->db->getOne($query, [
            ':oxorderid' => $shopOrderId,
            ':oxpaypalorderid' => $paypalOrderId
        ]);

        return (string) $id;
    }

    private function getShopOrderId(string $paypalOrderId): string
    {
        $query = "select oxorderid from oscpaypal_order where oxpaypalorderid = :oxpaypalorderid and LENGTH(oxorderid) > 0";
        $id = $this->db->getOne($query, [
            ':oxpaypalorderid' => $paypalOrderId
        ]);

        return (string) $id;
    }
}
