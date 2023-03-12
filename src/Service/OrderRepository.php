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
use OxidSolutionCatalysts\PayPal\Core\Constants;

class OrderRepository
{
    /** @var Database */
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
        $query = "select oxpaypalorderid from oscpaypal_order where oxorderid = :oxorderid";

        $id = $this->db->getOne($query, [
            ':oxorderid' => $shopOrderId
        ]);

        return (string) $id;
    }

    public function cleanUpNotFinishedOrders()
    {
        if (!$this->config->getConfigParam('oscPayPalCleanUpNotFinishedOrdersAutomaticlly')) {
            return;
        }

        $sessiontime = (int)$this->config->getConfigParam('oscPayPalStartTimeCleanUpOrders');
        $shopId = $this->config->getShopId();

        $query = "select oxid from oxorder where oxordernr = :oxordernr
            and oxtransstatus = :oxtransstatus
            and oxpaymenttype LIKE :oxpaymenttype
            and oxshopid = :oxshopid
            and oxorderdate < now() - interval :sessiontime MINUTE";
        /** @var \OxidEsales\Eshop\Core\Database\Adapter\Doctrine\ResultSet $result */
        $result = $this->db->select($query, [
            ':oxordernr' => '0',
            ':oxtransstatus' => 'NOT_FINISHED',
            ':oxpaymenttype' => '%oscpaypal%',
            ':oxshopid' => $shopId,
            ':sessiontime' => $sessiontime
        ]);
        if ($result != false && $result->count() > 0) {
            while (!$result->EOF) {
                $order = oxNew(EshopModelOrder::class);
                $id = $result->fields['oxid'];
                if ($order->load($id)) {
                    // storno
                    $order->cancelOrder();
                    // delete
                    $order->delete();
                }
                $result->fetchRow();
            }
        }
    }

    private function getId(string $shopOrderId, string $paypalOrderId = '', $payPalTransactionId = ''): string
    {
        $parameters = [
            ':oxorderid' => $shopOrderId
        ];

        if ($paypalOrderId) {
            $parameters[':oxpaypalorderid'] = $paypalOrderId;
        }

        if ($payPalTransactionId) {
            $parameters[':oscpaypaltransactionid'] = $payPalTransactionId;
        }

        $query = "select oxid from oscpaypal_order where oxorderid = :oxorderid";

        if ($paypalOrderId) {
            $query .= " and oxpaypalorderid = :oxpaypalorderid";
        }

        if ($payPalTransactionId) {
            $query .= " and oscpaypaltransactionid = :oscpaypaltransactionid";
        }

        $id = $this->db->getOne($query, $parameters);

        return (string) $id;
    }

    private function getShopOrderIdByPaypalOrderId(string $paypalOrderId): string
    {
        $query = "select oxorderid
            from oscpaypal_order
            where oxpaypalorderid = :oxpaypalorderid
            and LENGTH(oxorderid) > 0";
        $id = $this->db->getOne($query, [
            ':oxpaypalorderid' => $paypalOrderId
        ]);

        return (string) $id;
    }

    private function getShopOrderIdByPaypalTransactionId(string $paypalTransactionId): string
    {
        $query = "select oxorderid
            from oscpaypal_order
            where oscpaypaltransactionid = :oscpaypaltransactionid
            and LENGTH(oxorderid) > 0";
        $id = $this->db->getOne($query, [
            ':oscpaypaltransactionid' => $paypalTransactionId
        ]);

        return (string) $id;
    }
}
