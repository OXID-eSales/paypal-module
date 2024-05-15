<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use Exception;
use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Utils\PayPalAddressResponseToOxidAddress;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Service\UserRepository;
use OxidSolutionCatalysts\PayPal\Traits\JsonTrait;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;

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
        $basket = Registry::getSession()->getBasket();
        $defaultShippingPriceExpress = Registry::getConfig()->getConfigParam('oscPayPalDefaultShippingPriceExpress');
        if (!empty($defaultShippingPriceExpress) && Registry::getConfig()->getConfigParam('blCalculateDelCostIfNotLoggedIn') !== true)
        {
            $basket = $this->createExpressShippingBasket($defaultShippingPriceExpress);
        }
        if ($basket->getItemsCount() === 0) {
            $this->outputJson(['ERROR' => 'No Article in the Basket']);
        }

        $response = $this->getServiceFromContainer(PaymentService::class)->doCreatePayPalOrder(
            $basket,
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE,
            null,
            '',
            '',
            '',
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP,
            null,
            null,
            false,
            false,
            null
        );

        if ($response->id) {
            PayPalSession::storePayPalOrderId($response->id);
        }

        $this->outputJson($response);
    }
    /**
     * Clones the basket from the session and sets a specific express shipping price, ensuring the original basket remains unchanged.
     * This cloned basket with the adjusted shipping price is specifically used in scenarios like a PayPal checkout,
     * where a different shipping price might be necessary to prevent overcharge.
     * @param $defaultShippingPriceExpress
     * @return object|Basket|null
     */
    protected function createExpressShippingBasket($defaultShippingPriceExpress) {
        $basket = Registry::getSession()->getBasket();
        $basket = clone($basket);
        $oPrice    = oxNew(Price::class);
        $oPrice->setPrice((double)$defaultShippingPriceExpress);
        $basket->setDeliveryPrice($oPrice);
        $basket->calculateBasket(true);
        return $basket;
    }
    public function approveOrder()
    {
        $orderId = (string) Registry::getRequest()->getRequestEscapedParameter('orderID');
        $sessionOrderId = PayPalSession::getCheckoutOrderId();

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
            /** @var Logger $logger */
            $logger = $this->getServiceFromContainer(Logger::class);
            $logger->log('error', "Error on order capture call.", [$exception]);
        }

        if (!$this->getUser()) {
            $userRepository = $this->getServiceFromContainer(UserRepository::class);
            $paypalEmail = (string) $response->payer->email_address;

            $nonGuestAccountDetected = false;
            if ($userRepository->userAccountExists($paypalEmail)) {
                //got a non-guest account, so either we log in or redirect customer to login step
                $isLoggedIn = $this->handleUserLogin($response);
                $nonGuestAccountDetected = true;
            } else {
                //we need to use a guest account
                $userComponent = oxNew(UserComponent::class);
                $userComponent->createPayPalGuestUser($response);
            }
        }

        if ($user = $this->getUser()) {
            /** @var array $userInvoiceAddress */
            $userInvoiceAddress = $user->getInvoiceAddress();
            // add PayPal-Address as Delivery-Address
            $deliveryAddress = PayPalAddressResponseToOxidAddress::mapUserDeliveryAddress($response);
            try {
                $user->changeUserData(
                    $user->oxuser__oxusername->value,
                    '',
                    '',
                    $userInvoiceAddress,
                    $deliveryAddress
                );

                // use a deliveryaddress in oxid-checkout
                Registry::getSession()->setVariable('blshowshipaddress', false);

                $this->setPayPalPaymentMethod();
            } catch (StandardException $exception) {
                Registry::getUtilsView()->addErrorToDisplay($exception);
                $response->status = 'ERROR';
                PayPalSession::unsetPayPalOrderId();
                Registry::getSession()->getBasket()->setPayment(null);
            }
        } elseif ($nonGuestAccountDetected && !$isLoggedIn) {
            // PPExpress is actual no possible so we switch to PP-Standard
            $this->setPayPalPaymentMethod(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);
        } else {
            //TODO: we might end up in order step redirecting to start page without showing a message
            // if we have no user, we stop the process
            $response->status = 'ERROR';
            PayPalSession::unsetPayPalOrderId();
            Registry::getSession()->getBasket()->setPayment(null);
        }
        $this->outputJson($response);
    }

    public function cancelPayPalPayment()
    {
        PayPalSession::unsetPayPalOrderId();
        Registry::getSession()->getBasket()->setPayment(null);
        Registry::getUtils()->redirect(Registry::getConfig()->getShopSecureHomeURL() . 'cl=payment', false, 301);
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
                // Remove flag of "new item added" to not show "Item added" popup when returning to checkout from paypal
                $basket->isNewItemAdded();
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

    public function setPayPalPaymentMethod($defaultPayPalPaymentId = PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID): void
    {
        $session = Registry::getSession();
        $basket = $session->getBasket();
        $user = null;

        if ($activeUser = $this->getUser()) {
            $user = $activeUser;
        }

        $requestedPayPalPaymentId = $this->getRequestedPayPalPaymentId($defaultPayPalPaymentId);
        if ($session->getVariable('paymentid') !== $requestedPayPalPaymentId) {
            $basket->setPayment($requestedPayPalPaymentId);

            // get the active shippingSetId
            /** @psalm-suppress InvalidArgument */
            [, $shippingSetId,] =
                Registry::get(DeliverySetList::class)->getDeliverySetData('', $user, $basket);

            if ($shippingSetId) {
                $basket->setShipping($shippingSetId);
                $session->setVariable('sShipSet', $shippingSetId);
            }
            $session->setVariable('paymentid', $requestedPayPalPaymentId);
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

    protected function handleUserLogin(PayPalApiOrder $apiOrder): bool
    {
        $paypalConfig = oxNew(Config::class);
        $userComponent = oxNew(UserComponent::class);
        $isLoggedIn = false;

        if ($paypalConfig->loginWithPayPalEMail()) {
            $userComponent->loginPayPalCustomer($apiOrder);
            $isLoggedIn = true;
        } else {
            //NOTE: ProxyController must not redirect from create Order/approvaOrder methods,
            //      it has to show a json response in all cases.
            //tell order controller to redirect to checkout login
            Registry::getSession()->setVariable('oscpaypal_payment_redirect', true);
        }

        return $isLoggedIn;
    }

    protected function getRequestedPayPalPaymentId(
        $defaultPayPalPaymentId = PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID
    ): string {
        $paymentId = (string) Registry::getRequest()->getRequestEscapedParameter('paymentid');
        return PayPalDefinitions::isPayPalPayment($paymentId) ?
            $paymentId :
            $defaultPayPalPaymentId;
    }
}
