<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Traits;

use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Application\Model\Order;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Subscriptions\Money;
use OxidSolutionCatalysts\PayPalApi\Model\Subscriptions\Patch;
use OxidSolutionCatalysts\PayPalApi\Model\Subscriptions\ShippingDetail;
use OxidSolutionCatalysts\PayPalApi\Model\Subscriptions\SubscriptionActivateRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Subscriptions\SubscriptionCancelRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Subscriptions\SubscriptionCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Subscriptions\SubscriptionSuspendRequest;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPalApi\Model\Subscriptions\Subscription as PayPalSubscription;
use OxidSolutionCatalysts\PayPal\Repository\SubscriptionRepository;

trait AdminOrderFunctionTrait
{
    //TODO: check naming, trait is used for admin and shop frontend

    /**
     * Updates subscription
     *
     * @throws ApiException
     * @throws StandardException
     */
    public function update()
    {
        $request = Registry::getRequest();
        $subscriptionId = $this->getPayPalSubscription()->id;

        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $subscriptionService = $serviceFactory->getSubscriptionService();

        $shippingAddress = $request->getRequestEscapedParameter('shippingAddress');
        $shippingAmount = $request->getRequestEscapedParameter('shippingAmount');
        $billingInfo = $request->getRequestEscapedParameter('billingInfo');

        $patches = [];

        if ($shippingAddress) {
            $patches[] = new Patch([
                'op' => Patch::OP_REPLACE,
                'path' => '/subscriber/shipping_address',
                'value' => new ShippingDetail($shippingAddress),
            ]);
        }

        if ($shippingAmount) {
            $patches[] = new Patch([
                'op' => Patch::OP_REPLACE,
                'path' => '/shipping_amount',
                'value' => new Money($shippingAmount),
            ]);
        }

        $outstandingBalance = $billingInfo['outstanding-balance'] ?? null;

        if ($outstandingBalance) {
            $patches[] = new Patch([
               'op' => Patch::OP_REPLACE,
               'path' => '/billing_info/outstanding_balance',
               'value' => new Money($outstandingBalance),
            ]);
        }

        $subscriptionService->updateSubscription($subscriptionId, $patches);
    }

    /**
     * Updates subscription status
     */
    public function updateStatus()
    {
        $request = Registry::getRequest();
        $subscriptionId = $this->getPayPalSubscription()->id;

        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $subscriptionService = $serviceFactory->getSubscriptionService();

        if ($status = $request->getRequestEscapedParameter('status')) {
            $statusNote = $request->getRequestEscapedParameter('statusNote');
            switch ($status) {
                case 'ACTIVE':
                    $subscriptionService->activateSubscription(
                        $subscriptionId,
                        new SubscriptionActivateRequest(['reason' => $statusNote])
                    );
                    break;
                case 'SUSPENDED':
                    $subscriptionService->suspendSubscription(
                        $subscriptionId,
                        new SubscriptionSuspendRequest(['reason' => $statusNote])
                    );
                    break;
                case 'CANCELED':
                    $subscriptionService->cancelSubscription(
                        $subscriptionId,
                        new SubscriptionCancelRequest(['reason' => $statusNote])
                    );
                    break;
            }
        }
    }

    /**
     * Captures outstanding subscription fee
     */
    public function captureOutstandingFee()
    {
        $request = Registry::getRequest();
        $subscriptionId = $this->getPayPalSubscription()->id;

        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $subscriptionService = $serviceFactory->getSubscriptionService();

        $params = [
            'note' => $request->getRequestEscapedParameter('captureNote'),
            'capture_type' => SubscriptionCaptureRequest::CAPTURE_TYPE_OUTSTANDING_BALANCE,
            'amount' => $request->getRequestEscapedParameter('outstandingFee')
        ];

        $subscriptionService->captureAuthorizedPaymentOnSubscription(
            $subscriptionId,
            new SubscriptionCaptureRequest($params)
        );
    }

    private function getSubscriptionProduct(string $paypalSubscriptionId)
    {
        $subscriptionProduct = null;

        $sql = 'SELECT OXPAYPALSUBPRODID
                  FROM osc_paypal_subscription
                 WHERE PAYPALBILLINGAGREEMENTID = ?';

        $subProdId = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)
            ->getOne(
                $sql,
                [
                    $paypalSubscriptionId
                ]
            );

        if ($subProdId) {
            $sql = 'SELECT PAYPALPRODUCTID
                      FROM osc_paypal_subscription_product
                     WHERE OXID = ?';

            $subscriptionProductId = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)
                ->getOne(
                    $sql,
                    [
                        $subProdId
                    ]
                );

            if ($subscriptionProductId) {
                $subscriptionProduct = Registry::get(ServiceFactory::class)
                    ->getCatalogService()->showProductDetails($subscriptionProductId);
            }
        }

        return $subscriptionProduct;
    }

    /**
     * Is the object a subscription?
     * @param string|null $orderId
     * @return bool
     */
    public function isPayPalSubscription(string $orderId = null)
    {
        $repository = new SubscriptionRepository();
        $subscription = $repository->getSubscriptionByOrderId($orderId);
        return (bool) (
            isset($subscription['OXPARENTORDERID'])
            && $subscription['OXPARENTORDERID'] == ''
        );
    }

    /**
     * Is the object a subscription?
     * @param string|null $orderId
     * @return bool
     */
    public function isCancelRequestSended(string $orderId = null)
    {
        $repository = new SubscriptionRepository();
        return $repository->isCancelRequestSended($orderId);
    }

    /**
     * Is the object a Part-subscription?
     * @param string|null $orderId
     * @return bool
     */
    public function isPayPalPartSubscription(string $orderId = null)
    {
        $repository = new SubscriptionRepository();
        $subscription = $repository->getSubscriptionByOrderId($orderId);
        return (bool) (
            isset($subscription['OXPARENTORDERID'])
            && $subscription['OXPARENTORDERID'] !== ''
        );
    }

    /**
     * Get associated PayPal subscription
     *
     * @return PayPalSubscription
     * @throws ApiException
     * @throws StandardException
     */
    private function getPayPalSubscription(string $billingAgreementId): PayPalSubscription
    {
        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $subscriptionService = $serviceFactory->getSubscriptionService();

        return $subscriptionService->showSubscriptionDetails($billingAgreementId, 'last_failed_payment');
    }

    /**
     * template-getter getpayPalSubscription
     * @param string|null $orderId
     * @return obj
     */
    public function getParentSubscriptionOrder(string $orderId = null)
    {
        $result = null;
        $repository = new SubscriptionRepository();
        if ($subscription = $repository->getSubscriptionByOrderId($orderId)) {
            $parentOrder = oxNew(Order::class);
            if ($parentOrder->load($subscription['OXPARENTORDERID'])) {
                $result = $parentOrder;
            }
        }
        return $result;
    }
}
