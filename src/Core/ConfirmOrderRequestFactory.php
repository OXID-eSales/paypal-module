<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Language;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSource;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderValidateApplicationContext;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ConfirmOrderRequest;

/**
 * Class ConfirmOrderRequestFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class ConfirmOrderRequestFactory
{
    /**
     * @var ConfirmOrderRequest
     */
    private ConfirmOrderRequest $request;

    /**
     * @param Basket $basket
     * @param string $requestName Name of the RequestClass defined in PayPalClient
     *
     * @return ConfirmOrderRequest
     */
    public function getRequest(
        Basket $basket,
        string $requestName
    ): ConfirmOrderRequest {
        $request = $this->request = new ConfirmOrderRequest();

        $request->payment_source = $this->getPaymentSource($basket, $requestName);
        $request->application_context = $this->getApplicationContext();

        return $request;
    }

    protected function getPaymentSource(Basket $basket, string $requestName): PaymentSource
    {
        $user = $basket->getBasketUser();

        $userName = $user->getFieldData('oxfname') . ' ' . $user->getFieldData('oxlname');

        // get Billing CountryCode
        $country = oxNew(Country::class);
        $country->load($user->getFieldData('oxcountryid'));

        // check possible deliveryCountry
        $deliveryId = Registry::getSession()->getVariable("deladrid");
        $deliveryAddress = oxNew(Address::class);
        if ($deliveryId && $deliveryAddress->load($deliveryId)) {
            $country->load($deliveryAddress->getFieldData('oxcountryid'));
        }

        $paymentSource = new PaymentSource([
            $requestName => [
                'name' => $userName,
                'country_code' => $country->getFieldData('oxisoalpha2')
            ]
        ]);

        return $paymentSource;
    }

    /**
     * Sets application context
     *
     * @return OrderValidateApplicationContext
     */
    protected function getApplicationContext(): OrderValidateApplicationContext
    {
        $context = new OrderValidateApplicationContext();
        $language = new Language();
        $config = Registry::getConfig();

        $shopLanguageAbbr = $language->getLanguageAbbr();
        $context->locale = $shopLanguageAbbr . '-' . strtoupper($shopLanguageAbbr);
        $context->return_url = $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizeuapm';
        $context->cancel_url = $config->getSslShopUrl() . 'index.php?cl=order&fnc=canceluapm';

        return $context;
    }
}
