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
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Service\BaseService;

class VaultingService extends BaseService
{
    use ServiceContainer;

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

        $body = '';
        try {
            $response = $this->send('POST', $path, $params, $headers);
            if ($response) {
                $body = $response->getBody();
            }
        } catch (ApiException $e) {
        }

        try {
            $result = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $result = [];
        }
        return is_array($result) ? $result : [];
    }

    /**
     * Request a setup token either for card or for PayPal vaulting
     * @param bool $card
     * @return array
     * @throws JsonException
     */
    public function createVaultSetupToken(bool $card = false): array
    {
        if ($card) {
            $requestBody = [
                "payment_source" => [
                    "card" => [],
                ]
            ];
        } else {
            $requestBody = $this->getPaymentSourceForVaulting($card);
        }

        //add customerid if there already is one
        $user = Registry::getConfig()->getUser();
        if ($user) {
            $paypalCustomerId = $user->getFieldData("oscpaypalcustomerid");
            if ($paypalCustomerId) {
                $requestBody["customer"] = [
                    "id" => $paypalCustomerId
                ];
            }
        }

        $headers = $this->getVaultingHeaders();

        $path = '/v3/vault/setup-tokens';

        $body = '';
        try {
            $response = $this->send(
                'POST',
                $path,
                [],
                $headers,
                json_encode($requestBody, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT)
            );
            if ($response) {
                $body = $response->getBody();
            }
        } catch (ApiException $e) {
        }

        try {
            $result = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $result = [];
        }
        return is_array($result) ? $result : [];
    }

    /**
     * @param bool $card
     * @return array
     */
    public function getPaymentSourceForVaulting(bool $card): array
    {
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
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

        $shopName = $moduleSettings->getShopName();
        $lang = Registry::getLang();

        $description = sprintf($lang->translateString('OSC_PAYPAL_DESCRIPTION'), $shopName);

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
            "brand_name"          => $shopName,
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

    public function createVaultPaymentToken(string $setupToken): array
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

        $body = '';
        try {
            $response = $this->send('POST', $path, [], $headers, json_encode($requestBody, JSON_THROW_ON_ERROR));
            if ($response) {
                $body = $response->getBody();
            }
        } catch (ApiException $e) {

        }

        try {
            $result = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $result = [];
        }
        return is_array($result) ? $result : [];
    }

    public function getVaultPaymentTokens(string $paypalCustomerId): array
    {
        $viewConf = oxNew(ViewConfig::class);
        if (!$viewConf->getIsVaultingActive()) {
            return [];
        }

        $headers = [];
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $headers['PayPal-Partner-Attribution-Id'] = Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP;

        $path = '/v3/vault/payment-tokens?customer_id=' . $paypalCustomerId;

        $body = '';
        try {
            $response = $this->send('GET', $path, [], $headers);
            if ($response) {
                $body = $response->getBody();
            }
        } catch (ApiException $e) {
        }

        try {
            $result = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $result = [];
        }
        return is_array($result) ? $result : [];
    }

    public function getVaultPaymentTokenByIndex(string $paypalCustomerId, string $index): string
    {
        $paymentTokens = $this->getVaultPaymentTokens($paypalCustomerId);

        return $paymentTokens["payment_tokens"][$index] ?: '';
    }

    /**
     * @param string $paymentTokenId
     * @return bool
     */
    public function deleteVaultedPayment(string $paymentTokenId): bool
    {
        $headers = [];
        $headers['PayPal-Partner-Attribution-Id'] = Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP;

        $path = '/v3/vault/payment-tokens/' . $paymentTokenId;

        try {
            $response = $this->send('DELETE', $path, [], $headers);
            $result = $response && $response->getStatusCode() === 204;
        } catch (ApiException $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * @return array
     * @throws JsonException
     */
    protected function getVaultingHeaders(): array
    {
        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $headers['PayPal-Partner-Attribution-Id'] = Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP;
        return array_merge($headers, $this->getAuthHeaders());
    }

    /**
     * @throws JsonException
     */
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
