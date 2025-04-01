<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use JsonException;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Model\User;
use OxidEsales\EshopCommunity\Core\Field;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\JsonTrait;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;

class AjaxPaymentController extends ProxyController
{
    use JsonTrait;
    use ServiceContainer;

    /**
     *
     * TODO implement error reporting from front to log file
     * @throws JsonException
     */
    public function shopOrderError(): void
    {
        $data = $this->getRequestParameters();

        $shopOrderId = $data['shopOrderId'];
        /** @var PayPalOrder $oOrder */
        $oOrder = oxNew(Order::class);
        $oOrder->load($shopOrderId);

        $this->outputJson([
            'status' => 'success'
        ]);
    }

    /**
     * @throws JsonException
     */
    public function cancelShopOrder(): void
    {
        $data = $this->getRequestParameters();

        $shopOrderId = $data['shopOrderId'];
        /** @var PayPalOrder $oOrder */
        $oOrder = oxNew(Order::class);
        $oOrder->load($shopOrderId);
        $oOrder->delete();

        $this->outputJson([
            'status' => 'success'
        ]);
    }

    public function patchShopOrder(): void
    {
        $data = $this->getRequestParameters();
        $sessionShopOrderId = Registry::getSession()->getVariable('sess_challenge');
        $shopOrderId = $data['shopOrderId'];
        $payPalOrderId = $data['payPalOrderId'];
        $cancelSession = !$sessionShopOrderId || $shopOrderId !== $sessionShopOrderId;

        $paymentService = $this->getServiceFromContainer(PaymentService::class);

        /** @var \OxidEsales\EshopCommunity\Application\Model\User $oUser */
        $oUser = oxNew(User::class);
        $oUser->loadActiveUser();

        /** @var PayPalOrder $oOrder */
        $oOrder = oxNew(Order::class);
        $oOrder->load($shopOrderId);

        if($cancelSession){
            $this->outputJson([
                'status' => 'error',
                'message' => 'Order id mismatch error.', //@TODO improve errors messages
            ]);
        }
        $paymentsId = (string) $oOrder->getFieldData('oxpaymenttype');
        /** @var PayPalApiOrder $payPalOrder */
        $payPalOrder = $paymentService->fetchOrderFields($payPalOrderId, '');
        if ($oOrder->isPayPalOrderCompleted($payPalOrder)) {
            $oOrder->markOrderPaid();
            $transactionId = (string)$payPalOrder->purchase_units[0]->payments->captures[0]->id;
            $oOrder->setTransId($transactionId);
            $paymentService->trackPayPalOrder(
                $shopOrderId,
                $payPalOrderId,
                $paymentsId,
                PayPalApiOrder::STATUS_COMPLETED,
                $transactionId
            );
        } else {
            $this->outputJson([
                'status' => 'error',
                'message' => 'Order completion error.', //@TODO improve errors messages
            ]);
        }

        $this->outputJson([
            'status' => 'success',
            'oxid' => $oOrder->oxorder__oxid->value,
            'paypalOrderDetails' => $payPalOrder
        ]);
    }

    public function createShopOrder(): void
    {
        /** @var \OxidSolutionCatalysts\PayPal\Service\Payment $paymentService */
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $data = $this->getRequestParameters();
        $_POST['sDeliveryAddressMD5'] = $data['deliveryAddressId'];

        $oUser = oxNew(\OxidEsales\Eshop\Application\Model\User::class);
        $oUser->loadActiveUser();
        $oBasket = Registry::getSession()->getBasket();
        $oOrder = oxNew(Order::class);

        //finalizing ordering process (validating, storing order into DB, setting status)
        $iSuccess = $oOrder->finalizePayPalOrder($oBasket, $oUser, false);

        // performing special actions after user finishes order (assignment to special user groups)
        $oUser->onOrderExecute($oBasket, $iSuccess);

        $this->outputJson([
            'status' => 'success',
            'shopOrderId' => $oOrder->oxorder__oxid->value,
            'customId' => $paymentService->getCustomIdParameter($oOrder)
        ]);
    }

    /**
     * @return array|mixed
     * @throws \JsonException
     */
    public function getRequestParameters(): array
    {
        $body = file_get_contents('php://input');
        $data = [];

        if (!empty($body)) {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        return $data;
    }

    /**
     * @throws JsonException
     */
    public function updateOxUserWithPayPalCustomerId(): void
    {
        $data = $this->getRequestParameters();
        $user = $this->getUser();
        $user->oxuser__oscpaypalcustomerid = new Field($data['payPalCustomerId']);

        $user->save();

        $this->outputJson([
            'status' => 'success'
        ]);
    }
}
