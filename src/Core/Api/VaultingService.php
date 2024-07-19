<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Api;

use JsonException;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\State;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Service\BaseService;

class VaultingService extends BaseService
{
    public function generateUserIdToken($payPalCustomerId = false): array
    {
        $headers = [];
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $headers['PayPal-Partner-Attribution-Id'] = Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP;

        $params['grant_type']       = 'client_credentials';
        $params['response_type']    = 'id_token';

        if ($payPalCustomerId) {
            $params["target_customer_id"] = $payPalCustomerId;
        }

        $path = '/v1/oauth2/token';

        $response = $this->send('POST', $path, $params, $headers);
        $body = $response->getBody();

        return json_decode((string)$body, true);
    }

    /**
     * Request a setup token either for card or for PayPal vaulting
     * @param bool $card
     * @return array
     * @throws ApiException
     * @throws JsonException
     */
    public function createVaultSetupToken(bool $card = false): array
    {
        if ($card) {
            $body = [
                "payment_source" => [
                    "card" => [],
                ]
            ];
        } else {
            $body = $this->getPaymentSourceForVaulting($card);
        }

        //add customerid if there already is one
        if ($paypalCustomerId = Registry::getConfig()->getUser()->getFieldData("oscpaypalcustomerid")) {
            $body["customer"] = [
                "id" => $paypalCustomerId
            ];
        }

        $headers = $this->getVaultingHeaders();

        $path = '/v3/vault/setup-tokens';

        $response = $this->send(
            'POST',
            $path,
            [],
            $headers,
            json_encode($body, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT)
        );
        $body = $response->getBody();

        return json_decode((string)$body, true);
    }

    /**
     * @param bool $card
     * @return array[]
     */
    public function getPaymentSourceForVaulting(bool $card): array
    {
        $viewConf   = Registry::get(ViewConfig::class);
        $config     = Registry::getConfig();
        $user       = $viewConf->getUser();

        $country = oxNew(Country::class);
        $country->load($user->getFieldData('oxcountryid'));

        $state = oxNew(State::class);
        $state->loadByIdAndCountry(
            $user->getFieldData('oxstateid'),
            $user->getFieldData('oxcountryid')
        );

        $shopName = Registry::getConfig()->getActiveShop()->getFieldData('oxname');
        $lang = Registry::getLang();

        $description = sprintf($lang->translateString('OSC_PAYPAL_DESCRIPTION'), $shopName);

        $activeShop = Registry::getConfig()->getActiveShop();

        $name                   = $user->getFieldData("oxfname");
        $name                   .= $user->getFieldData("oxlname");
        $address                = [
            "address_line_1"    => $user->getFieldData('oxstreet') . " " . $user->getFieldData('oxstreetnr'),
            "address_line_2"    => $user->getFieldData('oxcompany') . " " . $user->getFieldData('oxaddinfo'),
            "admin_area_1"      => $state->getFieldData('oxtitle'),
            "admin_area_2"      => $user->getFieldData('oxcity'),
            "postal_code"       => $user->getFieldData('oxzip'),
            "country_code"      => $country->oxcountry__oxisoalpha2->value,
        ];
        $locale                 =
            strtolower($country->oxcountry__oxisoalpha2->value)
            . '-'
            . strtoupper($country->oxcountry__oxisoalpha2->value);
        $experience_context     = [
            "brand_name"          => $activeShop->getFieldData('oxname'),
            "locale"              => $locale,
            "return_url"          => $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizepaypalsession',
            "cancel_url"          => $config->getSslShopUrl() . 'index.php?cl=order&fnc=cancelpaypalsession',
//            "shipping_preference" => "SET_PROVIDED_ADDRESS",
        ];

        if ($card) {
            $paymentSource = [
                "card" => [
                    "name" => "$name",
                    "billing_address"       => $address,
                    "verification_method"   => "SCA_WHEN_REQUIRED",
                    "experience_context"    => $experience_context,
                    "attributes" => [
                        "verification" => [
                            "method" => "SCA_WHEN_REQUIRED"
                        ],
                        "vault" => [
                            "store_in_vault" => "ON_SUCCESS"
                        ]
                    ],
                ]
            ];
        } else {
            $paymentSource = [
                "payment_source" => [
                    "paypal" => [
                        "description"   => $description,
                        "shipping"      => [
                            "name"      => [
                                "full name" => $name
                            ],
                            "address"   => $address
                        ],
                        "usage_type" => "MERCHANT",
                        "customer_type" => "CONSUMER",
                        "permit_multiple_payment_tokens" => true,
                        "usage_pattern" => "IMMEDIATE",
                        "experience_context" => $experience_context
                    ]
                ]
            ];
        }

        return $paymentSource;
    }

    public function createVaultPaymentToken($setupToken)
    {
        $headers = $this->getVaultingHeaders();

        $path = '/v3/vault/payment-tokens';

        $requestBody = [
            "payment_source" => [
                "token" => [
                    "id"    => $setupToken,
                    "type"  => "SETUP_TOKEN",
                ]
            ]
        ];

        $response = $this->send('POST', $path, [], $headers, json_encode($requestBody));
        $responseBody = $response->getBody();

        return json_decode((string)$responseBody, true);
    }

    public function getVaultPaymentTokens($paypalCustomerId)
    {
        $viewConf = oxNew(ViewConfig::class);
        if (!$viewConf->getIsVaultingActive()) {
            return null;
        }

        $path = '/v3/vault/payment-tokens?customer_id=' . $paypalCustomerId;

        $response = $this->send('GET', $path);
        $body = $response->getBody();

        return json_decode((string)$body, true);
    }

    public function getVaultPaymentTokenByIndex($paypalCustomerId, $index)
    {
        $paymentTokens = $this->getVaultPaymentTokens($paypalCustomerId);

        return $paymentTokens["payment_tokens"][$index];
    }

    /**
     * @param $paymentTokenId
     * @return bool
     */
    public function deleteVaultedPayment($paymentTokenId)
    {
        $path = '/v3/vault/payment-tokens/' . $paymentTokenId;

        $response = $this->send('DELETE', $path);

        return $response->getStatusCode() == 204;
    }

    /**
     * @return array
     */
    protected function getVaultingHeaders(): array
    {
        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $headers['PayPal-Partner-Attribution-Id'] = Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP;
        $headers = array_merge($headers, $this->getAuthHeaders());
        return $headers;
    }

    protected function getAuthHeaders(): array
    {
        if (!$this->client->isAuthenticated()) {
            $this->client->auth();
        }

        $headers = [];
        $headers['Authorization'] = 'Bearer ' . $this->client->getTokenResponse();

        return $headers;
    }
}
