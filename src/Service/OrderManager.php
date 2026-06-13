<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use Exception;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\JsonTrait;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidEsales\Eshop\Core\Config as EshopCoreConfig;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Psr\Log\LoggerInterface;
class OrderManager
{
    use JsonTrait;
    use ServiceContainer;

    /** @var QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    /** @var EshopCoreConfig */
    private $config;

    /**
     * @var Payment
     */
    private $paymentService;

    /** @var object|Basket|null */
    private $basket;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        EshopCoreConfig $config,
        PaymentService $paymentService,
        LoggerInterface $logger
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->config = $config;
        $this->paymentService = $paymentService;
        $this->basket = Registry::getSession()->getBasket();
        $this->logger = $logger;
    }

    /**
     * Creates the shop order using the same mechanics as previously in AjaxPaymentController::createShopOrder
     */
    public function createShopOrder(?string $paymentId = null): ?array
    {
        $user = $this->getUser($this->basket);
        if (!$user || !$user->loadActiveUser()) {
            $this->logger->log(
                'debug',
                'Error during create shop order: loadActiveUser'
            );
            return null;
        }

        // The payment id passed by the PayPal checkout flow is authoritative for
        // the order created here: always apply it, overriding any payment method
        // left in the basket by a previous, abandoned checkout (e.g. an Amazon Pay
        // express selection). The earlier "only when empty" guard ignored the
        // PayPal payment id whenever a foreign payment was still set on the basket.
        if (!empty($paymentId)) {
            $this->basket->setPayment($paymentId);
        }

        // Safety net: this method always runs the PayPal checkout path, where the
        // isPayPalPaymentCheckout flag (set below) makes finalizeOrder skip the
        // regular payment execution and mark the order OK. The basket payment must
        // therefore be a PayPal payment here — otherwise a stale foreign basket
        // payment would be finalized as a paid order without its real payment ever
        // running. Abort cleanly; the callers turn a null result into an error
        // response instead of creating a mispaid order.
        if (!PayPalDefinitions::isPayPalPayment((string) $this->basket->getPaymentId())) {
            $this->logger->log(
                'error',
                'Aborting create shop order: basket payment is not a PayPal payment',
                [
                    'basketPaymentId' => (string) $this->basket->getPaymentId(),
                    'requestedPaymentId' => (string) $paymentId,
                ]
            );
            return null;
        }

        $order = oxNew(Order::class);
        $session = Registry::getSession();
        $session->deleteVariable('sess_challenge');
        $session->setVariable('isPayPalPaymentCheckout', true);
        // finalizing an ordering process (validating, storing order into DB, setting status)
        try {
            $success = $order->finalizeOrder($this->basket, $user);
        } catch (Exception $exception) {
            $this->logger->log(
                'debug',
                'Error during create shop order: finalizeOrder',
                [$exception->getMessage()]
            );
            return null;
        }
        $session->deleteVariable('isPayPalPaymentCheckout');
        $session->setVariable('sess_challenge', $this->basket->getOrderId());

        // performing special actions after user finishes order (assignment to special user groups)
        $user->onOrderExecute($this->basket, $success);

        return [
            'shopOrderId' => $order->getId(),
            'customId' => $this->paymentService->getCustomIdParameter($order)
        ];
    }

    private function getUser(?Basket $basket = null): ?User
    {
        $basket = $basket ?: Registry::getSession()->getBasket();
        $user = $basket ? $basket->getUser() : null;
        if (!$user) {
            /** @var User $userObj */
            $userObj = oxNew(User::class);
            return $userObj;
        }
        return $user;
    }
}
