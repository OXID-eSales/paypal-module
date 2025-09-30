<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\JsonTrait;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidEsales\Eshop\Core\Config as EshopCoreConfig;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class OrderManager
{
    use JsonTrait;
    use ServiceContainer;

    /** @var QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    /** @var EshopCoreConfig */
    private $config;
    /**
     * @var \OxidSolutionCatalysts\PayPal\Service\Payment
     */
    private $paymentService;

    /** @var object|\OxidEsales\Eshop\Application\Model\Basket|null */
    private $basket;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        EshopCoreConfig $config,
        PaymentService $paymentService
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->config = $config;
        $this->paymentService = $paymentService;
        $this->basket = Registry::getSession()->getBasket();
        ;
    }

    /**
     * Creates the shop order using the same mechanics as previously in AjaxPaymentController::createShopOrder
     */
    public function createShopOrder(?string $paymentId = null): ?array
    {
        $user = $this->getUser($this->basket);
        if (!$user || !$user->loadActiveUser()) {
            $this->outputJson(['status' => 'error']);

            return null;
        }

        if (empty($this->basket->getPaymentId()) && !empty($paymentId)) {
            $this->basket->setPaymentId($paymentId);
        }

        $order = oxNew(Order::class);
        Registry::getSession()->deleteVariable('sess_challenge');

        // finalizing an ordering process (validating, storing order into DB, setting status)
        $success = $order->finalizeOrder($this->basket, $user, false);

        Registry::getSession()->setVariable('sess_challenge', $this->basket->getOrderId());

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
