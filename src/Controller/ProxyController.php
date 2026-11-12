<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use Exception;
use JsonException;
use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\Utils\AmountFormatter;
use OxidSolutionCatalysts\PayPal\Service\Factory\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalCancelReason;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\PayPalSession;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Utils\PayPalAddressResponseToOxidAddress;
use OxidSolutionCatalysts\PayPal\Service\BasketStockGuard;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\OrderManager;
use OxidSolutionCatalysts\PayPal\Service\OrderProcessTrackingService;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Service\UserAddressPaypalService;
use OxidSolutionCatalysts\PayPal\Service\UserRepository;
use OxidSolutionCatalysts\PayPal\Service\PayPalUrlService;
use OxidSolutionCatalysts\PayPal\Traits\JsonTrait;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Traits\PayPalBasketTrait;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AddressPortable;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Payer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PurchaseUnitRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ShippingDetail;
use Psr\Log\LoggerInterface;

/**
 * Server side interface for PayPal smart buttons.
 */
class ProxyController extends FrontendController
{
    use JsonTrait;
    use ServiceContainer;
    use PayPalBasketTrait;

    /** @var UserAddressPaypalService */
    private $userAddressPaypalService;

    /** @var OrderProcessTrackingService */
    private $orderProcessTrackingService;

    /**
     * @var \OxidSolutionCatalysts\PayPal\Service\OrderRepository
     */
    private $orderRepository;

    /** @var \OxidSolutionCatalysts\PayPal\Service\OrderManager */
    private $orderManager;

    public function __construct()
    {
        parent::__construct();
        $this->orderProcessTrackingService = $this->getServiceFromContainer(OrderProcessTrackingService::class);
        $this->userAddressPaypalService = $this->getServiceFromContainer(UserAddressPaypalService::class);
        $this->orderRepository = $this->getServiceFromContainer(OrderRepository::class);
        $this->orderManager = $this->getServiceFromContainer(OrderManager::class);
    }

    public function createOrder(): void
    {
        $this->cancelPendingPayPalOrder();

        $session = Registry::getSession();
        $basket = $session->getBasket();
        $config = Registry::getConfig();
        $this->addToBasket();
        if ($this->rejectBasketChangedByStock()) {
            return;
        }
        $paymentId = Registry::getRequest()->getRequestParameter('paymentid');
        if ($paymentId === PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID) {
            $this->setPayPalPaymentMethod($paymentId);
        } else {
            $this->setPayPalPaymentMethod();
        }
        $paymentId = $basket->getPaymentId();

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);

        $defaultShippingPriceExpress = (double) $moduleSettings->getDefaultShippingPriceForExpress();
        $calculateDelCostIfNotLoggedIn = (bool)$config->getConfigParam('blCalculateDelCostIfNotLoggedIn');
        $isDeliverySet = (bool)$session->getVariable('sShipSet');
        if ($basket && $defaultShippingPriceExpress && !$calculateDelCostIfNotLoggedIn && !$isDeliverySet) {
            $basket->addShippingPriceForExpress($defaultShippingPriceExpress);
        }
        if ($basket->getItemsCount() === 0) {
            $this->outputJson(['ERROR' => 'No Article in the Basket']);
        }

        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $captureStrategy = $moduleSettings->getPayPalStandardCaptureStrategy();
        $intent = $captureStrategy === 'directly' ? OrderRequest::INTENT_CAPTURE : OrderRequest::INTENT_AUTHORIZE;
        $userAction = $paymentId === PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID ?
            OrderRequestFactory::USER_ACTION_CONTINUE : OrderRequestFactory::USER_ACTION_PAY_NOW;
        $returnUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizepaypalsession';
        $cancelUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=cancelpaypalsession';
        $response = $paymentService->doCreatePayPalOrder(
            $basket,
            $intent,
            $userAction,
            null,
            '',
            '',
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP,
            $returnUrl,
            $cancelUrl,
            false
        );

        if ($response->id) {
            PayPalSession::storePayPalOrderId($response->id);

            // Persist mapping in oscpaypal_order immediately so webhooks can find
            // the shop order even if the customer never returns from PayPal.
            $shopOrderId = $basket->getOrderId();
            if (!empty($shopOrderId)) {
                $paymentService->trackPayPalOrder(
                    $shopOrderId,
                    $response->id,
                    $paymentId,
                    PayPalApiOrder::STATUS_CREATED
                );
            }
        }

        $response !== null ?
            $this->outputJson($response) :
            $this->outputJson(['ERROR' => 'Error on create PayPal Order call.']);
    }

    /**
     * @throws JsonException
     */
    public function createGooglePayOrder()
    {
        $data = json_decode(
            file_get_contents('php://input'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $shippingAddress = new AddressPortable();
        $shippingAddress->address_line_1 = $data['shippingAddress']['address1'] ?? '';
        $shippingAddress->address_line_2 = $data['shippingAddress']['address2'] ?? '';
        $shippingAddress->address_line_3 = $data['shippingAddress']['address3'] ?? '';
        $shippingAddress->postal_code = $data['shippingAddress']['postalCode'] ?? '';
        $shippingAddress->admin_area_2 = $data['shippingAddress']['locality'] ?? '';
        $shippingAddress->admin_area_1 = $data['shippingAddress']['administrativeArea'] ?? '';
        $shippingAddress->country_code = $data['shippingAddress']['countryCode'] ?? '';

        if (PayPalSession::isPayPalExpressOrderActive()) {
            //TODO: improve
            $this->outputJson(
                [
                    'ERROR' => 'PayPal session already started.' . PayPalSession::isPayPalExpressOrderActive()
                ]
            );
        }
        $paymentId = (string)Registry::getSession()->getVariable('paymentid')
            ?: PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID;

        $this->addToBasket();
        if ($this->rejectBasketChangedByStock()) {
            return;
        }
        $this->setPayPalPaymentMethod($paymentId);
        $basket = Registry::getSession()->getBasket();

        if ($basket->getItemsCount() === 0) {
            $this->outputJson(['ERROR' => 'No Article in the Basket']);
        }

        $payPalUrlService = $this->getServiceFromContainer(PayPalUrlService::class);
        $isLoggedIn = false;
        $nonGuestAccountDetected = false;
        $response = $this->getServiceFromContainer(PaymentService::class)->doCreatePayPalOrder(
            $basket,
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE,
            null,
            '',
            '',
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP,
            $payPalUrlService->getReturnUrl(),
            $payPalUrlService->getCancelUrl(),
            false
        );

        if (!$this->isCreatedPayPalOrder($response)) {
            return;
        }

        if ($response->id) {
            PayPalSession::storePayPalOrderId($response->id);

            // Persist mapping in oscpaypal_order immediately so webhooks can find
            // the shop order even if the customer never returns from PayPal.
            $shopOrderId = $basket->getOrderId();
            if (!empty($shopOrderId)) {
                $this->getServiceFromContainer(PaymentService::class)->trackPayPalOrder(
                    $shopOrderId,
                    $response->id,
                    $paymentId ?: PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID,
                    PayPalApiOrder::STATUS_CREATED
                );
            }
        }

        if (!$this->getUser()) {
            $nonGuestAccountDetected = $this->prepareWalletVisitorWithoutLogin(
                $response,
                (string)($data['email'] ?? ''),
                $shippingAddress,
                (string)($data['shippingAddress']['name'] ?? ''),
                (string)($data['shippingAddress']['phoneNumber'] ?? '')
            );
        }

        if ($user = $this->getUser()) {
            /** @var array $userInvoiceAddress */
            $userInvoiceAddress = $user->getInvoiceAddress();
            // add PayPal-Address as Delivery-Address
            if (($response !== null) && !empty($response->purchase_units[0]->shipping)) {
                $response->purchase_units[0]->shipping->address = $shippingAddress;
                $response->purchase_units[0]->shipping->name->full_name = $data['shippingAddress']['name'] ?? '';
                $deliveryAddress = PayPalAddressResponseToOxidAddress::mapUserDeliveryAddress($response);
                if ($deliveryAddress['oxaddress__oxfname'] !== '' && $deliveryAddress['oxaddress__oxstreet'] !== '') {
                    try {
                        $this->userAddressPaypalService->changePayPalUserData(
                            $user,
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
                }
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


    /**
     * @throws JsonException
     */
    public function approveOrder()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $orderId = (string)Registry::getRequest()->getRequestEscapedParameter('orderID');
        $sessionOrderId = PayPalSession::getCheckoutOrderId();
        if (!empty($data['orderID']) && $orderId === '') {
            $orderId = $data['orderID'];
        }
        if (!$orderId || ($orderId !== $sessionOrderId)) {
            //TODO: improve
            $this->outputJson(['ERROR' => 'OrderId not found in PayPal session.']);
        }

        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $orderService = $serviceFactory->getOrderService();
        $orderService->setTrackingId($this->orderProcessTrackingService->getTrackingId());
        $nonGuestAccountDetected = false;
        $isLoggedIn = false;

        try {
            $response = $orderService->showOrderDetails(
                $orderId,
                '',
                Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP
            );
        } catch (Exception $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
            $logger->log('warning', 'Could not fetch PayPal order details for ProxyController approveOrder: ' . $exception->getMessage(), [$exception]);
        }

        if (!$this->getUser() && $response) {
            $userRepository = $this->getServiceFromContainer(UserRepository::class);
            $paypalEmail = (string)$response->payer->email_address;

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
                $this->userAddressPaypalService->changePayPalUserData(
                    $user,
                    $userInvoiceAddress,
                    $deliveryAddress,
                );

                // Force shipping recalculation after address change from PayPal
                // (sShipSet may still contain old delivery set that is invalid for new country)
                Registry::getSession()->deleteVariable('sShipSet');
                Registry::getSession()->getBasket()->setShipping();

                $paymentId = Registry::getSession()->getVariable('paymentid');
                // use a deliveryaddress in oxid-checkout
                Registry::getSession()->setVariable('blshowshipaddress', false);
                if ($paymentId === PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID) {
                    $this->setPayPalPaymentMethod($paymentId);
                } else {
                    $this->setPayPalPaymentMethod();
                }
                if ($paymentId === PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID) {
                    $this->setPayPalPaymentMethod($paymentId);
                } else {
                    $this->setPayPalPaymentMethod();
                }
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
        PayPalSession::unsetPayPalSession(false);
        $redirect = Registry::getRequest()->getRequestParameter('redirect');
        if ($redirect === "1") {
            Registry::getUtils()->redirect(
                Registry::getConfig()->getShopSecureHomeURL() . 'cl=payment',
                false,
                301
            );
        }
        exit;
    }

    /**
     * Cancel any pending PayPal order with associated shop order to prevent session conflicts.
     * Handles the case where a customer starts PayPal Standard payment, leaves the popup open,
     * and then starts a PayPal Express payment in a new tab.
     */
    private function cancelPendingPayPalOrder(): void
    {
        $session = Registry::getSession();
        $shopOrderId = (string)$session->getVariable('sess_challenge');

        $paymentService = $this->getServiceFromContainer(PaymentService::class);

        if (!$paymentService->getTemporaryOrder()) {
            return;
        }

        $payPalOrderId = PayPalSession::getCheckoutOrderId();


        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
        $logger->log('info', sprintf(
            'Cancelling pending PayPal order (shop: %s, paypal: %s) before starting Express checkout',
            $shopOrderId,
            $payPalOrderId
        ));

        $paymentService->removeTemporaryOrder($shopOrderId);
        PayPalSession::unsetPayPalSession(false);
    }

    /**
     * Checks the basket against the current stock before a PayPal order is created from it.
     *
     * Without this, the PayPal window showed and the buyer approved the total of an article
     * that another customer had bought in the meantime; the shop found out only when the
     * order was submitted. When the stock changed the basket, no PayPal order is created:
     * the answer asks the button to reload the page, which shows the corrected basket and
     * the reason (BasketStockGuard queues the messages).
     *
     * @return bool true if the request was answered and must not continue
     */
    private function rejectBasketChangedByStock(): bool
    {
        $basket = Registry::getSession()->getBasket();
        if (!$basket || !$basket->getProductsCount()) {
            return false;
        }

        /** @var BasketStockGuard $basketStockGuard */
        $basketStockGuard = $this->getServiceFromContainer(BasketStockGuard::class);
        if (!$basketStockGuard->correctBasket($basket)) {
            return false;
        }

        // a temporary shop order or PayPal order of an earlier attempt describes the old basket
        $this->getServiceFromContainer(PaymentService::class)
            ->removeTemporaryOrder('', PayPalCancelReason::UNKNOWN);
        PayPalSession::unsetPayPalOrderId();

        $this->outputJson([
            'ERROR' => 'The basket changed because of the current stock',
            'status' => 'ERROR',
            'reload' => true
        ]);

        return true;
    }

    protected function handleUserLogin(PayPalApiOrder $apiOrder): bool
    {
        $paypalConfig = oxNew(Config::class);
        $userComponent = oxNew(UserComponent::class);

        // Auto-login via the PayPal email only counts when the merchant enabled it AND it actually
        // succeeds. loginPayPalCustomer() returns false when the email has no matching 'user'
        // account — e.g. it belongs to an admin account (blocked since the 2.9.0 security fix) or to
        // no shop account at all. In every not-logged-in case (option off, or on but login failed)
        // we route the customer to the login step with the same neutral message, so that an admin
        // email is not distinguishable from the ordinary "please log in" case.
        if ($paypalConfig->loginWithPayPalEMail() && $userComponent->loginPayPalCustomer($apiOrder)) {
            return true;
        }

        //NOTE: ProxyController must not redirect from create Order/approveOrder methods,
        //      it has to show a json response in all cases.
        //tell order controller to redirect to checkout login (shows OSC_PAYPAL_LOG_IN_TO_CONTINUE)
        Registry::getSession()->setVariable('oscpaypal_payment_redirect', true);

        return false;
    }

    /**
     * Google Pay / Apple Pay for a visitor who is not logged in: puts the data
     * from the wallet into the PayPal order response and creates a guest
     * account when no customer account uses the e-mail address.
     *
     * The e-mail address comes from the browser, PayPal has not confirmed it.
     * It must therefore never log the visitor in (0008026): when a customer
     * account with that address exists, the visitor is sent to the login step
     * - the same as with the automatic login switched off - and nothing is
     * written into that account.
     *
     * @return bool true when a customer account with that e-mail address exists
     */
    protected function prepareWalletVisitorWithoutLogin(
        PayPalApiOrder $response,
        string $email,
        AddressPortable $shippingAddress,
        string $fullName,
        string $phoneNumber
    ): bool {
        $purchaseUnitRequest = new PurchaseUnitRequest();
        $purchaseUnitRequest->shipping = new ShippingDetail();
        $purchaseUnitRequest->shipping->address = $shippingAddress;
        $purchaseUnitRequest->shipping->initName()->full_name = $fullName;
        $response->purchase_units = [$purchaseUnitRequest];

        $response->payer = new Payer();
        $response->payer->email_address = $email;
        $response->payer->initPhone()->phone_number->national_number = $phoneNumber;

        $userRepository = $this->getServiceFromContainer(UserRepository::class);
        if ($userRepository->userAccountExists($email)) {
            //tell order controller to redirect to checkout login (shows OSC_PAYPAL_LOG_IN_TO_CONTINUE)
            Registry::getSession()->setVariable('oscpaypal_payment_redirect', true);
            return true;
        }

        $userComponent = oxNew(UserComponent::class);
        $userComponent->createPayPalGuestUser($response);

        return false;
    }

    /**
     * PayPal may refuse to create the order, doCreatePayPalOrder() then returns
     * null. Answer with JSON instead of running into an error further down.
     *
     * @param mixed $response
     */
    private function isCreatedPayPalOrder($response): bool
    {
        if ($response instanceof PayPalApiOrder && !empty($response->id)) {
            return true;
        }

        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
        $logger->log('warning', sprintf(
            'ProxyController: PayPal did not create the order for payment method %s',
            (string)Registry::getSession()->getVariable('paymentid')
        ));

        PayPalSession::unsetPayPalOrderId();
        Registry::getSession()->getBasket()->setPayment(null);
        $this->outputJson(['ERROR' => 'Error on create PayPal Order call.']);

        return false;
    }

    public function getPaymentRequestLines()
    {
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        try {
            $basket = Registry::getSession()->getBasket();
            if ($basket->getItemsCount() === 0) {
                throw new Exception('No Article in the Basket');
            }

            $deliveryCost = $basket->getDeliveryCost();
            if (!$deliveryCost) {
                throw new Exception('Delivery cost calculation failed');
            }
            $deliveryBruttoPrice = $deliveryCost->getBruttoPrice();

            $sVat = 0.0;
            foreach ($basket->getProductVats(false) as $VATitem) {
                $sVat += $VATitem;
            }

            $paymentRequest = [
                'total' => [
                    'label' => $moduleSettings->getShopName(),
                    'amount' => $basket->getPriceForPayment(),
                    'type' => 'final'
                ],
                'lineItems' => [
                    [
                        'label' => 'Subtotal',
                        'amount' => AmountFormatter::format((float) $basket->getBruttoSum()),
                        'type' => 'final'
                    ],
                    [
                        'label' => 'Tax',
                        'amount' => AmountFormatter::format((float) $sVat),
                        'type' => 'final'
                    ],
                    [
                        'label' => 'Shipping',
                        'amount' => AmountFormatter::format((float) $deliveryBruttoPrice),
                        'type' => 'final'
                    ]
                ]
            ];
            $this->outputJson($paymentRequest);
        } catch (Exception $e) {
            $this->outputJson(['ERROR' => $e->getMessage()]);
        }
    }

    public function createApplepayOrder()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $shippingData = $data['data'];
        $_POST['ord_agb'] = (int)filter_var($data['checkAgbTop'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $_POST['oxdownloadableproductsagreement'] = (int)filter_var($data['oxdownloadableproductsagreement'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $_POST['oxserviceproductsagreement'] = (int)filter_var($data['oxserviceproductsagreement'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $shippingAddress = new AddressPortable();
        $shippingAddress->address_line_1 = $shippingData['shippingContact']['addressLines'][0] ?? '';
        $shippingAddress->address_line_2 = $shippingData['shippingContact']['emailAddress'] ?? '';
        $shippingAddress->address_line_3 = $shippingData['shippingContact']['address3'] ?? '';
        $shippingAddress->postal_code = $shippingData['shippingContact']['postalCode'] ?? '';
        $shippingAddress->admin_area_2 = $shippingData['shippingContact']['locality'] ?? '';
        $shippingAddress->admin_area_1 = $shippingData['shippingContact']['administrativeArea'] ?? '';
        $shippingAddress->country_code = $shippingData['shippingContact']['countryCode'] ?? '';
        if (PayPalSession::isPayPalExpressOrderActive()) {
            //TODO: improve
        }
        $paymentId = (string)Registry::getSession()->getVariable('paymentid')
            ?: PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID;
        $nonGuestAccountDetected = false;
        $isLoggedIn = false;

        $this->addToBasket();
        if ($this->rejectBasketChangedByStock()) {
            return;
        }
        $this->setPayPalPaymentMethod($paymentId);
        $basket = Registry::getSession()->getBasket();

        if ($basket->getItemsCount() === 0) {
            $this->outputJson(['ERROR' => 'No Article in the Basket']);
        }

        $payPalUrlService = $this->getServiceFromContainer(PayPalUrlService::class);
        $response = $this->getServiceFromContainer(PaymentService::class)->doCreatePayPalOrder(
            $basket,
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE,
            null,
            '',
            '',
            Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP,
            $payPalUrlService->getReturnUrl(),
            $payPalUrlService->getCancelUrl(),
            false
        );
        if (!$this->isCreatedPayPalOrder($response)) {
            return;
        }

        if ($response->id) {
            PayPalSession::storePayPalOrder((array)$response);

            // Persist mapping in oscpaypal_order immediately so webhooks can find
            // the shop order even if the customer never returns from PayPal.
            $shopOrderId = $basket->getOrderId();
            if (!empty($shopOrderId)) {
                $this->getServiceFromContainer(PaymentService::class)->trackPayPalOrder(
                    $shopOrderId,
                    $response->id,
                    $paymentId ?: PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID,
                    PayPalApiOrder::STATUS_CREATED
                );
            }
        }

        if (!$this->getUser()) {
            $nonGuestAccountDetected = $this->prepareWalletVisitorWithoutLogin(
                $response,
                (string)($data['email'] ?? ''),
                $shippingAddress,
                (string)($shippingData['shippingContact']['name'] ?? ''),
                (string)($shippingData['shippingContact']['phoneNumber'] ?? '')
            );
        }

        if ($user = $this->getUser()) {
            /** @var array $userInvoiceAddress */
            $userInvoiceAddress = $user->getInvoiceAddress();

            // add PayPal-Address as Delivery-Address
            if (($response !== null) && !empty($response->purchase_units[0]->shipping)) {
                $response->purchase_units[0]->shipping->address = $shippingAddress;
                $response->purchase_units[0]->shipping->name->full_name =
                    $shippingData['shippingContact']['name'] ?? '';
                $deliveryAddress = PayPalAddressResponseToOxidAddress::mapUserDeliveryAddress($response);
                if (
                    $deliveryAddress['oxaddress__oxfname'] !== ''
                    && $deliveryAddress['oxaddress__oxstreet'] !== ''
                ) {
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

                        $this->setPayPalPaymentMethod($paymentId);
                    } catch (StandardException $exception) {
                        //   Registry::getUtilsView()->addErrorToDisplay($exception);
                        // $response->status = 'ERROR';
                        //     PayPalSession::unsetPayPalOrderId();
                        Registry::getSession()->getBasket()->setPayment(null);
                    }
                }
            }
        } elseif ($nonGuestAccountDetected && !$isLoggedIn) {
            // PPExpress is actual no possible so we switch to PP-Standard
            $this->setPayPalPaymentMethod(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);
        } else {
            //TODO: we might end up in order step redirecting to start page without showing a message
            // if we have no user, we stop the process
            ////     $response->status = 'ERROR';
            //     PayPalSession::unsetPayPalOrderId();
            //  Registry::getSession()->getBasket()->setPayment(null);
        }
        $this->outputJson($response);
    }
}
