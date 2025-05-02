<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Language;
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
    use CustomerAddressHelper;

    /**
     * @var ConfirmOrderRequest
     */
    private $request;

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
        return $request;
    }

    protected function getPaymentSource(Basket $basket, string $requestName): PaymentSource
    {
        $userName = $this->getUserNameFromBasket($basket);
        $country = $this->getCountryFromBasket($basket);

        //@todo remove the next line, until client has added googlepay
        if ($requestName === 'googlepay') {
            $requestName = 'google_pay';
            $paymentSource = $this->getGooglePayPaymentSource($basket, $requestName);
        } else {
            $user = $basket->getBasketUser();
            $paymentSource = new PaymentSource([
                $requestName => [
                    'name' => $userName,
                    'email' => $user->getFieldData('oxusername'),
                    'country_code' => $country->getFieldData('oxisoalpha2'),
                    'experience_context' => $this->getExperienceContext()
                ]
            ]);
        }

        return $paymentSource;
    }
}
