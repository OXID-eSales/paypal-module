<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Exception\LanguageNotFoundException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Language;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderConfirmApplicationContext;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSource;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ConfirmOrderRequest;
use OxidSolutionCatalysts\PayPalApi\Pui\ExperienceContext;
use OxidSolutionCatalysts\PayPal\Traits\CustomerAddressHelper;

/**
 * Class ConfirmOrderRequestFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 */
class ConfirmOrderRequestFactory
{
    use ServiceContainer;
    use CustomerAddressHelper;

    /**
     * @var ConfirmOrderRequest
     */
    private $request;

    /**
     * @param Basket $basket
     * @param string $paymentSourceId Name of the $paymentSourceId
     *
     * @return ConfirmOrderRequest
     * @throws LanguageNotFoundException
     */
    public function getRequest(
        Basket $basket,
        string $paymentSourceId
    ): ConfirmOrderRequest {
        $request = $this->request = new ConfirmOrderRequest();
        $request->payment_source = $this->getPaymentSource($basket, $paymentSourceId);
        return $request;
    }

    protected function getPaymentSource(Basket $basket, string $paymentSourceId): PaymentSource
    {
        $userName = $this->getUserNameFromBasket($basket);
        $country = $this->getCountryFromBasket($basket);

        //@todo remove the next line, until client has added googlepay
        if ($paymentSourceId === PayPalDefinitions::PAYMENT_SOURCE_GOOGLEPAY) {
            $paymentSource = $this->getGooglePayPaymentSource($basket, $paymentSourceId);
        } else {
            $user = $basket->getBasketUser();
            $paymentSource = new PaymentSource([
                $paymentSourceId => [
                    'name' => $userName,
                    'email' => $user->getFieldData('oxusername'),
                    'country_code' => $country->getFieldData('oxisoalpha2'),
                    'experience_context' => $this->getExperienceContext()
                ]
            ]);
        }

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
