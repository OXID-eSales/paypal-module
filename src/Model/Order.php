<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);


namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Capture;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Catalog\Product as PayPalProduct;
use OxidSolutionCatalysts\PayPalApi\Model\Subscriptions\Subscription as PayPalSubscription;
use OxidSolutionCatalysts\PayPalApi\Service\Orders;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Traits\AdminOrderFunctionTrait;

/**
 * PayPal Eshop model order class
 *
 * @mixin \OxidEsales\Eshop\Application\Model\Order
 */
class Order extends Order_parent
{
    use AdminOrderFunctionTrait;

    /**
     * PayPal order information
     *
     * @var PayPalOrder
     */
    protected $payPalOrder;

    /**
     * PayPal order Id
     *
     * @var string
     */
    protected $payPalOrderId;

    /**
     * PayPal Billing Agreement Id;
     *
     * @var string
     */
    protected $payPalBillingAgreementId;

    /**
     * PayPal Product Id
     *
     * @var string
     */
    protected $payPalProductId;

    /**
     * Get PayPal order object for the current active order object
     * Result is cached and returned on subsequent calls
     *
     * @return PayPalOrder
     * @throws ApiException
     */
    public function getPayPalOrder(): PayPalOrder
    {
        if (!$this->payPalOrder) {
            /** @var Orders $orderService */
            $orderService = Registry::get(ServiceFactory::class)->getOrderService();
            $this->payPalOrder = $orderService->showOrderDetails($this->getPayPalOrderIdForOxOrderId());
        }

        return $this->payPalOrder;
    }

    /**
     * Update order oxpaid to current time.
     */
    public function markOrderPaid()
    {
        parent::_setOrderStatus('OK');

        $db = DatabaseProvider::getDb();
        $utilsDate = Registry::getUtilsDate();
        $date = date('Y-m-d H:i:s', $utilsDate->getTime());

        $query = 'update oxorder set oxpaid=? where oxid=?';
        $db->execute($query, [$date, $this->getId()]);

        //updating order object
        $this->oxorder__oxpaid = new Field($date);
    }

    /**
     * Returns PayPal order id.
     *
     * @param string|null $oxId
     *
     * @return string
     */
    public function getPayPalOrderIdForOxOrderId(string $oxId = null)
    {
        if (is_null($this->payPalOrderId)) {
            $this->payPalOrderId = '';
            $oxId = is_null($oxId) ? $this->getId() : $oxId;
            $table = 'oxps_paypal_order';
            $shopId = $this->getShopId();
            $params = [$table . '.oxorderid' => $oxId, $table . '.oxshopid' => $shopId];

            $paypalOrderObj = oxNew(BaseModel::class);
            $paypalOrderObj->init($table);
            $select = $paypalOrderObj->buildSelectString($params);

            if ($data = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getRow($select)) {
                $this->payPalOrderId = $data['oxpaypalorderid'];
            }
        }
        return $this->payPalOrderId;
    }

    /**
     * Returns PayPal Session id.
     *
     * @param string|null $oxId
     *
     * @return string
     */
    public function getPayPalProductIdForOxOrderId(string $oxId = null)
    {
        if (is_null($this->payPalProductId)) {
            $this->payPalProductId = '';
            $oxId = is_null($oxId) ? $this->getId() : $oxId;
            $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

            $sql = 'SELECT OXPAYPALSUBPRODID
                      FROM oxps_paypal_subscription
                     WHERE PAYPALBILLINGAGREEMENTID = ?';

            /** @var $paypalSubscriptionId $subProdId @Todo Specify this!*/
            $paypalSubscriptionId = null;

            $subProdId = $db->getOne(
                $sql,
                [
                    $paypalSubscriptionId
                ]
            );

            if ($subProdId) {
                $sql = 'SELECT PAYPALPRODUCTID
                          FROM oxps_paypal_subscription_product
                         WHERE OXID = ?';

                $this->payPalProductId = $db->getOne(
                    $sql,
                    [
                        $subProdId
                    ]
                );
            }
        }
        return $this->payPalProductId;
    }

    /**
     * Returns PayPal BillingAgreementId
     *
     * @param string|null $oxId
     *
     * @return string
     */
    public function getPayPalBillingAgreementIdForOxOrderId(string $oxId = null)
    {
        if (is_null($this->payPalBillingAgreementId)) {
            $this->payPalBillingAgreementId = '';
            $oxId = is_null($oxId) ? $this->getId() : $oxId;
            $table = 'oxps_paypal_subscription';
            $shopId = $this->getShopId();
            $params = [$table . '.oxorderid' => $oxId, $table . '.oxshopid' => $shopId];

            $paypalOrderObj = oxNew(BaseModel::class);
            $paypalOrderObj->init($table);
            $select = $paypalOrderObj->buildSelectString($params);

            if ($data = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getRow($select)) {
                $this->payPalBillingAgreementId = $data['paypalbillingagreementid'];
            }
        }
        return $this->payPalBillingAgreementId;
    }

    /**
     * Checks if the order was paid using PayPal
     *
     * @return bool
     */
    public function paidWithPayPal(): bool
    {
        return (bool) ($this->getPayPalOrderIdForOxOrderId() || $this->getPayPalBillingAgreementIdForOxOrderId());
    }

    /**
     * Get order payment capture or null if not captured
     *
     * @return Capture|null
     * @throws ApiException
     */
    public function getOrderPaymentCapture(): ?Capture
    {
        return $this->getPayPalOrder()->purchase_units[0]->payments->captures[0] ?? null;
    }



    /**
     * template-getter getPayPalSubscriptionForHistory
     * @return obj
     */
    public function getPayPalSubscriptionForHistory()
    {
        $billingAgreementId = $this->getPayPalBillingAgreementIdForOxOrderId();
        return $this->getPayPalSubscription($billingAgreementId);
    }
}
