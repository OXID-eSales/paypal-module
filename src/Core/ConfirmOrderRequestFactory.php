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
use OxidEsales\Eshop\Core\Exception\LanguageNotFoundException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Language;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderConfirmApplicationContext;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSource;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ConfirmOrderRequest;
use OxidSolutionCatalysts\PayPalApi\Pui\ExperienceContext;

/**
 * Class ConfirmOrderRequestFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class ConfirmOrderRequestFactory
{
    /**
     * @var ConfirmOrderRequest
     */
    private $request;

    /**
     * @param Basket $basket
     * @param string $requestName Name of the RequestClass defined in PayPalClient
     *
     * @return ConfirmOrderRequest
     * @throws LanguageNotFoundException
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

    /**
     * @throws \OxidEsales\Eshop\Core\Exception\LanguageNotFoundException
     */
    protected function getPaymentSource(Basket $basket, string $requestName)
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
        //@todo remove the next line, until client has added googlepay
        if ($requestName === 'googlepay') {
            $requestName = 'google_pay';
            $paymentSource = new \stdClass();

            // Dynamically adding properties to the stdClass object
            $paymentSource->$requestName = new \stdClass();
            $paymentSource->$requestName->name = $userName;
            $paymentSource->$requestName->country_code = $country->getFieldData('oxisoalpha2');
            $paymentSource->$requestName->attributes = new \stdClass();
            $paymentSource->$requestName->attributes->verification = new \stdClass();
            $paymentSource->$requestName->attributes->verification->method = 'SCA_ALWAYS';
        } else {
            $paymentSource = new PaymentSource([
                $requestName => [
                    'name' => $userName,
                    'email' => $user->getFieldData('oxusername'),
                    'country_code' => $country->getFieldData('oxisoalpha2')
                ]
            ]);
        }

        $paymentSource->$requestName->experience_context = $this->getExperienceContext();

        return $paymentSource;
    }

    /**
     * Sets application context
     *
     * @throws LanguageNotFoundException
     * @return OrderConfirmApplicationContext
     */
    protected function getExperienceContext(): \JsonSerializable
    {
        $context = new ExperienceContext();
        $language = new Language();
        $config = Registry::getConfig();
        $shopLanguageAbbr = $language->getLanguageAbbr();
        $context->locale = $shopLanguageAbbr . '-' . strtoupper($shopLanguageAbbr);
        $context->return_url = $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizepaypalsession';
        $context->cancel_url = $config->getSslShopUrl() . 'index.php?cl=order&fnc=cancelpaypalsession';

        return $context;
    }
}
