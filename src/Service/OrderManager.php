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

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /** @var object|Basket|null */
    private $basket;

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

        if (empty($this->basket->getPaymentId()) && !empty($paymentId)) {
            $this->basket->setPayment($paymentId);
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
