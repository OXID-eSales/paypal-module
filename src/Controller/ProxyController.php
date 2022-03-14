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
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\UserRepository;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Repository\SubscriptionRepository;
use OxidSolutionCatalysts\PayPal\Core\Utils\PayPalAddressResponseToOxidAddress;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;

/**
 * Server side interface for PayPal smart buttons.
 */
class ProxyController extends FrontendController
{
    use ServiceContainer;

    public function createOrder()
    {
        if (PayPalSession::isPayPalExpressOrderActive()) {
            //TODO: improve
            $this->outputJson(['ERROR' => 'PayPal session already started.']);
        }

        $this->addToBasket();
        $this->setPayPalPaymentMethod();

        $response = $this->getServiceFromContainer(PaymentService::class)->doCreatePayPalOrder(
            Registry::getSession()->getBasket(),
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE,
            null,
            '',
            '',
            '',
            $this->getPayPalPartnerAttributionId()
        );

        if ($response->id) {
            PayPalSession::storePayPalOrderId($response->id);
        }

        $this->outputJson($response);
    }

    public function approveOrder()
    {
        $orderId = (string) Registry::getRequest()->getRequestEscapedParameter('orderID');
        $sessionOrderId = PayPalSession::getcheckoutOrderId();

        if (!$orderId || ($orderId !== $sessionOrderId)) {
            //TODO: improve
            $this->outputJson(['ERROR' => 'OrderId not found in PayPal session.']);
        }

        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $service = $serviceFactory->getOrderService();

        try {
            $response = $service->showOrderDetails($orderId, '');
        } catch (Exception $exception) {
            Registry::getLogger()->error("Error on order capture call.", [$exception]);
        }

        if (!$this->getUser()) {
            $userRepository = $this->getServiceFromContainer(UserRepository::class);
            $paypalEmail = (string) $response->payer->email_address;

            if ($userRepository->userAccountExists($paypalEmail)) {
                //got a non-guest account, so either we log in or redirect customer to login step
                $this->handleUserLogin($response);
            } else {
                //we need to use a guest account
                $userComponent = oxNew(UserComponent::class);
                $userComponent->createPayPalGuestUser($response);
            }
        }

        if ($user = $this->getUser()) {
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

    protected function addToBasket($qty = 1): void
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

        if ($session->getVariable('paymentid') !== PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID) {
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
                if (array_key_exists(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID, $paymentList)) {
                    $possibleDeliverySets[] = $deliverySet->getId();
                }
            }

            if (count($possibleDeliverySets)) {
                $basket->setPayment(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
                $shippingSetId = reset($possibleDeliverySets);
                $basket->setShipping($shippingSetId);
                $session->setVariable('sShipSet', $shippingSetId);
                $session->setVariable('paymentid', PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
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

    protected function handleUserLogin(PayPalApiOrder $apiOrder): void
    {
        $paypalConfig = oxNew(Config::class);
        $userComponent = oxNew(UserComponent::class);

        if ($paypalConfig->loginWithPayPalEMail()) {
            $userComponent->loginPayPalCustomer($apiOrder);
        } else {
            //TODO: we should redirect to user step/login page via exception/ShopControl
            //tell order controller to redirect to checkout login
            Registry::getSession()->setVariable('oscpaypal_payment_redirect', true);
        }
    }

    protected function getPayPalPartnerAttributionId(): string
    {
        return Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_EXPRESS;
    }
}
