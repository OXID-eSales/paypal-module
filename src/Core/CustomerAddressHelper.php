<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use JsonSerializable;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\PayPalUrlService;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderExperienceContext;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSource;

trait CustomerAddressHelper
{
    protected function getUserNameFromBasket($basket): string
    {
        $user = $basket->getBasketUser();
        return $user->getFieldData('oxfname') . ' ' . $user->getFieldData('oxlname');
    }

    protected function getCountryFromBasket($basket): Country
    {
        $user = $basket->getBasketUser();
        $country = oxNew(Country::class);
        $country->load($user->getFieldData('oxcountryid'));
        $deliveryId = Registry::getSession()->getVariable("deladrid");
        $deliveryAddress = oxNew(Address::class);
        if ($deliveryId && $deliveryAddress->load($deliveryId)) {
            $country->load($deliveryAddress->getFieldData('oxcountryid'));
        }

        return $country;
    }

    protected function getExperienceContext(
        ?string $userAction = null,
        ?string $returnUrl = null,
        ?string $cancelUrl = null,
        ?bool $setProvidedAddress = null
    ): JsonSerializable {
        $context = new OrderExperienceContext();

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $context->brand_name = $moduleSettings->getShopName();
        $context->shipping_preference = 'GET_FROM_FILE';
        $context->landing_page = 'LOGIN';
        $config = Registry::getConfig();

        if (empty($returnUrl)) {
            $returnUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizepaypalsession';
        }

        if (empty($cancelUrl)) {
            $cancelUrl = $config->getSslShopUrl() . 'index.php?cl=order&fnc=cancelpaypalsession';
        }

        if ($userAction) {
            $context->user_action = $userAction;
        }
        if ($returnUrl) {
            $context->return_url = $returnUrl;
        }
        if ($cancelUrl) {
            $context->cancel_url = $cancelUrl;
        }
        if ($setProvidedAddress) {
            $context->shipping_preference = "SET_PROVIDED_ADDRESS";
        }
        return $context;
    }

    protected function getGooglePayPaymentSource(Basket $basket, string $requestName): PaymentSource
    {
        $userName = $this->getUserNameFromBasket($basket);
        $country = $this->getCountryFromBasket($basket);

        return new PaymentSource([
            $requestName => [
                'name' => $userName,
                'country_code' => $country->getFieldData('oxisoalpha2'),
                'attributes' => [
                    'verification' => [
                        'method' => 'SCA_ALWAYS'
                    ],
                ],
                'experience_context' => $this->getExperienceContext(),
            ]
        ]);
    }
}
