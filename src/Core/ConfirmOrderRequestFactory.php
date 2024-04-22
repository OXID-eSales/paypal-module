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
use OxidSolutionCatalysts\PayPal\Model\User;
use OxidSolutionCatalysts\PayPal\Traits\SessionDataGetter;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderConfirmApplicationContext;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSource;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ConfirmOrderRequest;

/**
 * Class ConfirmOrderRequestFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class ConfirmOrderRequestFactory
{
    use SessionDataGetter;

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
        $request = new ConfirmOrderRequest();

        $request->payment_source = $this->getPaymentSource($basket, $requestName);
        $request->application_context = $this->getApplicationContext();

        return $request;
    }

    protected function getPaymentSource(Basket $basket, string $requestName): PaymentSource
    {
        /** @var User $user */
        $user = $basket->getBasketUser();
        $userName = $user->getPaypalStringData('oxfname') . ' ' . $user->getPaypalStringData('oxlname');

        // get Billing CountryCode
        /** @var \OxidSolutionCatalysts\PayPal\Model\Country $country */
        $country = oxNew(Country::class);
        $country->load($user->getPaypalStringData('oxcountryid'));

        // check possible deliveryCountry
        $deliveryId = self::getSessionStringVariable("deladrid");
        /** @var \OxidSolutionCatalysts\PayPal\Model\Address $deliveryAddress */
        $deliveryAddress = oxNew(Address::class);
        if ($deliveryId && $deliveryAddress->load($deliveryId)) {
            $country->load($deliveryAddress->getPaypalStringData('oxcountryid'));
        }

        $paymentSource = new PaymentSource([
            $requestName => [
                'name' => $userName,
                'country_code' => $country->getPaypalStringData('oxisoalpha2')
            ]
        ]);

        return $paymentSource;
    }

    /**
     * Sets application context
     *
     * @return OrderConfirmApplicationContext
     */
    protected function getApplicationContext(): OrderConfirmApplicationContext
    {
        $context = new OrderConfirmApplicationContext();
        $language = new Language();
        $config = Registry::getConfig();

        $shopLanguageAbbr = $language->getLanguageAbbr();
        $context->locale = $shopLanguageAbbr . '-' . strtoupper($shopLanguageAbbr);
        $context->return_url = $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizepaypalsession';
        $context->cancel_url = $config->getSslShopUrl() . 'index.php?cl=order&fnc=cancelpaypalsession';

        return $context;
    }
}
