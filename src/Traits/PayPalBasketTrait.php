<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Traits;

use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;

trait PayPalBasketTrait
{
    /**
     * Add article to basket from request parameters
     */
    protected function addToBasket(): void
    {
        $basket = Registry::getSession()->getBasket();
        $utilsView = Registry::getUtilsView();
        $aSel = Registry::getRequest()->getRequestParameter('sel');
        $qty = (double)Registry::getRequest()->getRequestParameter('amountToBasket') ?? 0;

        if ($aid = (string)Registry::getRequest()->getRequestEscapedParameter('aid')) {
            try {
                if (!$this->itemExists($basket, $aid, $qty)) {
                    $basket->addToBasket($aid, $qty, $aSel);
                    $basket->isNewItemAdded();
                }
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

    /**
     * Set PayPal payment method in session
     */
    protected function setPayPalPaymentMethod(
        string $defaultPayPalPaymentId = PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID
    ): void {
        $session = Registry::getSession();
        $basket = $session->getBasket();
        $user = null;

        if (method_exists($this, 'getUser')) {
            $user = $this->getUser();
        }

        $requestedPayPalPaymentId = $this->getRequestedPayPalPaymentId($defaultPayPalPaymentId);
        if ($session->getVariable('paymentid') !== $requestedPayPalPaymentId) {
            $basket->setPayment($requestedPayPalPaymentId);
            $session->setVariable('paymentid', $requestedPayPalPaymentId);
        }
        $this->getActiveShippingSetId($session, $user, $basket);
    }

    /**
     * Get active shipping set ID
     */
    private function getActiveShippingSetId($session, $user, $basket): void
    {
        $shippingSetId = $session->getVariable('sShipSet');

        if ($shippingSetId) {
            return;
        }

        /** @psalm-suppress InvalidArgument */
        [, $shippingSetId,] =
            Registry::get(DeliverySetList::class)->getDeliverySetData('', $user, $basket);

        if ($shippingSetId) {
            $basket->setShipping($shippingSetId);
            $session->setVariable('sShipSet', $shippingSetId);
        }
    }

    /**
     * Get requested PayPal payment ID from request
     */
    protected function getRequestedPayPalPaymentId(
        string $defaultPayPalPaymentId = PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID
    ): string {
        $paymentId = (string)Registry::getRequest()->getRequestEscapedParameter('paymentid');
        return PayPalDefinitions::isPayPalPayment($paymentId) ?
            $paymentId :
            $defaultPayPalPaymentId;
    }

    /**
     * Check if item already exists in basket
     */
    private function itemExists(?Basket $basket, ?string $articleOxid, ?int $amountToBasket): bool
    {
        if ($basket === null) {
            return false;
        }

        $basketContents = $basket->getContents();
        foreach ($basketContents as $basketItem) {
            if ($basketItem->getProductId() === $articleOxid && $basketItem->getAmount() === $amountToBasket) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tries to fetch user delivery country ID
     */
    protected function getDeliveryCountryId(): string
    {
        $config = Registry::getConfig();
        $user = method_exists($this, 'getUser') ? $this->getUser() : null;
        $countryId = '';

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