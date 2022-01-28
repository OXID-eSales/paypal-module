<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use Exception;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Application\Model\PaymentList;
use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Repository\SubscriptionRepository;
use OxidSolutionCatalysts\PayPal\Core\Utils\PayPalAddressResponseToOxidAddress;

/**
 * Server side interface for PayPal smart buttons.
 */
class ProxyController extends FrontendController
{
    public function createOrder()
    {
        $context = (string)Registry::getRequest()->getRequestEscapedParameter('context', 'continue');

        $this->addToBasket();
        $this->setPayPalPaymentMethod();

        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $service = $serviceFactory->getOrderService();

        /** @var OrderRequestFactory $requestFactory */
        $requestFactory = Registry::get(OrderRequestFactory::class);
        $request = $requestFactory->getRequest(
            Registry::getSession()->getBasket(),
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE
        );

        try {
            $response = $service->createOrder($request, '', '');
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on order create call.", [$exception]);
        }

        if ($response->id) {
            PayPalSession::storePayPalOrderId($response->id);
        }

        $this->outputJson($response);
    }

    public function captureOrder()
    {
        $context = (string)Registry::getRequest()->getRequestEscapedParameter('context', 'continue');

        if ($orderId = (string)Registry::getRequest()->getRequestEscapedParameter('orderID')) {
            /** @var ServiceFactory $serviceFactory */
            $serviceFactory = Registry::get(ServiceFactory::class);
            $service = $serviceFactory->getOrderService();
            $request = new OrderCaptureRequest();

            try {
                $response = $service->showOrderDetails($orderId, '');
            } catch (Exception $exception) {
                Registry::getLogger()->error("Error on order capture call.", [$exception]);
            }

            if (!$user = $this->getUser()) {
                // create user if it is not exists
                $userComponent = oxNew(UserComponent::class);
                $userComponent->createPayPalGuestUser($response);
                $this->setPayPalPaymentMethod();
            } else {
                // add PayPal-Address as Delivery-Address
                $deliveryAddress = PayPalAddressResponseToOxidAddress::mapAddress($response, 'oxaddress__');
                $user->changeUserData(
                    $user->oxuser__oxusername->value,
                    null,
                    null,
                    $user->getInvoiceAddress(),
                    $deliveryAddress
                );
            }

            $this->outputJson($response);
        }
    }

    public function approveOrder()
    {
        if ($orderId = (string)Registry::getRequest()->getRequestEscapedParameter('orderID')) {
            /** @var ServiceFactory $serviceFactory */
            $serviceFactory = Registry::get(ServiceFactory::class);
            $service = $serviceFactory->getOrderService();
            $config = oxNew(Config::class);

            try {
                $response = $service->showOrderDetails($orderId, '');
            } catch (Exception $exception) {
                Registry::getLogger()->error("Error on order capture call.", [$exception]);
            }

            $userComponent = oxNew(UserComponent::class);

            if (!$user = $this->getUser()) {
                // login if account exists
                if (($config->loginWithPayPalEMail() && $userComponent->loginPayPalCustomer($response)) || $userComponent->createPayPalGuestUser($response)) {
                    $user = $this->getUser();
                // create guest-session
//                } else {
//                    $userComponent->createPayPalGuestUser($response);
                }
            }

            if ($user) {
                // add PayPal-Address as Delivery-Address
                $deliveryAddress = PayPalAddressResponseToOxidAddress::mapAddress($response, 'oxaddress__');
                $user->changeUserData(
                    $user->oxuser__oxusername->value,
                    null,
                    null,
                    $user->getInvoiceAddress(),
                    $deliveryAddress
                );

                // use a deliveryaddress in oxid-checkout
                Registry::getSession()->setVariable('blshowshipaddress', false);

                $this->setPayPalPaymentMethod();
            }
            $this->outputJson($response);
        }
    }

    public function createSubscriptionOrder()
    {
        // Remove all items from the basket
        // because subscriptions can only work with one subscription product at a time
        $session = Registry::getSession();
        $session->getBasket()->deleteBasket();

        $subscriptionPlanId = Registry::getRequest()->getRequestEscapedParameter('subscriptionPlanId');
        $session->setVariable('subscriptionPlanIdForBasket', $subscriptionPlanId);

        $this->addToBasket();
        $this->setPayPalPaymentMethod();
        $this->outputJson([true]);
    }

    public function saveSubscriptionOrder()
    {
        PayPalSession::subscriptionIsProcessing();

        $billingAgreementId = Registry::getRequest()->getRequestEscapedParameter('billingAgreementId');
        $subscriptionPlanId = Registry::getRequest()->getRequestEscapedParameter('subscriptionPlanId');

        $repository = new SubscriptionRepository();
        $repository->saveSubscriptionOrder($billingAgreementId, $subscriptionPlanId);
    }

    public function cancelPayPalPayment()
    {
        PayPalSession::unsetPayPalOrderId();
        Registry::getSession()->getBasket()->setPayment(null);
        Registry::getUtils()->redirect(Registry::getConfig()->getShopHomeUrl() . 'cl=payment', false, 301);
    }

    public function sendCancelRequest()
    {
        $repository = new SubscriptionRepository();
        $lang = Registry::getLang();
        $orderId = Registry::getRequest()->getRequestEscapedParameter('orderId');

        if (
            $orderId &&
            !$repository->isCancelRequestSended($orderId)
        ) {
            $repository->setCancelRequestSended($orderId);

            $order = oxNew(Order::class);
            $order->load($orderId);

            $user = oxNew(User::class);
            $user->load($order->oxorder__oxuserid->value);

            $userName = $user->oxuser__oxfname->value . ' ' . $user->oxuser__oxlname->value;
            $customerNo = $user->oxuser__oxcustnr->value;
            $orderNo = $order->oxorder__oxordernr->value;

            $message = sprintf(
                $lang->translateString('OSC_PAYPAL_SUBSCRIPTION_UNSUBSCRIBE_MAIL'),
                $userName,
                $customerNo,
                $orderNo
            );

            $mailer = oxNew(Email::class);
            $mailer->sendContactMail(
                '',
                $lang->translateString('OSC_PAYPAL_SUBSCRIPTION_UNSUBSCRIBE_HEAD'),
                $message
            );
        }
        $this->outputJson([true]);
    }

    /**
     * Encodes and sends response as json
     *
     * @param $response
     */
    protected function outputJson($response)
    {
        $utils = Registry::getUtils();
        $utils->setHeader('Content-Type: application/json');
        $utils->showMessageAndExit(json_encode($response));
    }

    public function addToBasket($qty = 1): void
    {
        $basket = Registry::getSession()->getBasket();
        $utilsView = Registry::getUtilsView();

        if ($aid = (string)Registry::getRequest()->getRequestEscapedParameter('aid')) {
            try {
                $basket->addToBasket($aid, $qty);
            } catch (OutOfStockException $exception) {
                $utilsView->addErrorToDisplay($exception);
            } catch (ArticleInputException $exception) {
                $utilsView->addErrorToDisplay($exception);
            } catch (NoArticleException $exception) {
                $utilsView->addErrorToDisplay($exception);
            }
            $basket->calculateBasket(false);
        }
    }

    public function setPayPalPaymentMethod(): void
    {
        $session = Registry::getSession();
        $basket = $session->getBasket();
        $countryId = $this->getDeliveryCountryId;
        $user = null;

        if ($activeUser = $this->getUser()) {
            $user = $activeUser;
        }

        if ($session->getVariable('paymentid') !== 'oxidpaypal') {
            $possibleDeliverySets = [];

            $deliverySetList = Registry::get(DeliverySetList::class)
            ->getDeliverySetList(
                $user,
                $countryId
            );
            foreach ($deliverySetList as $deliverySet) {
                $paymentList = Registry::get(PaymentList::class)->getPaymentList(
                    $deliverySet->getId(),
                    $basket->getPrice()->getBruttoPrice(),
                    $user
                );
                if (array_key_exists('oxidpaypal', $paymentList)) {
                    $possibleDeliverySets[] = $deliverySet->getId();
                }
            }

            if (count($possibleDeliverySets)) {
                $basket->setPayment('oxidpaypal');
                $shippingSetId = reset($possibleDeliverySets);
                $basket->setShipping($shippingSetId);
                $session->setVariable('sShipSet', $shippingSetId);
                $session->setVariable('paymentid', 'oxidpaypal');
            }
        }
    }

    /**
     * Tries to fetch user delivery country ID
     *
     * @return string
     */
    protected function getDeliveryCountryId()
    {
        $config = Registry::getConfig();
        $user = $this->getUser();

        $countryId = null;

        if (!$user) {
            $homeCountry = $config->getConfigParam('aHomeCountry');
            if (is_array($homeCountry)) {
                $countryId = current($homeCountry);
            }
        } else {
            if ($delCountryId = $config->getGlobalParameter('delcountryid')) {
                $countryId = $delCountryId;
            } elseif ($addressId = Registry::getSession()->getVariable('deladrid')) {
                $deliveryAddress = oxNew(Address::class);
                if ($deliveryAddress->load($addressId)) {
                    $countryId = $deliveryAddress->oxaddress__oxcountryid->value;
                }
            }

            if (!$countryId) {
                $countryId = $user->oxuser__oxcountryid->value;
            }
        }
        return $countryId;
    }
}
