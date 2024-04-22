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
use OxidSolutionCatalysts\PayPal\Model\Shop;
use OxidSolutionCatalysts\PayPal\Model\User;
use OxidSolutionCatalysts\PayPal\Traits\TranslationDataGetter;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Service\BaseService;

class VaultingService extends BaseService
{
    use TranslationDataGetter;

    public function generateUserIdToken(?bool $payPalCustomerId = false): array
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
        $method = 'post';

        $response = $this->send($method, $path, $params, $headers);
        $body = $response->getBody();
        $decode = json_decode((string)$body, true);
        return is_array($decode) ? (array)$decode : [];
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
        $method = 'post';

        $response = $this->send(
            $method,
            $path,
            [],
            $headers,
            json_encode($body, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT)
        );
        $body = $response->getBody();
        $decode = json_decode((string)$body, true);
        return is_array($decode) ? (array)$decode : [];
    }

    /**
     * @param bool $card
     * @return array[]
     */
    public function getPaymentSourceForVaulting(bool $card): array
    {
        $viewConf   = Registry::get(ViewConfig::class);
        $config     = Registry::getConfig();
        /** @var User $user */
        $user       = $viewConf->getUser();

        $country = oxNew(Country::class);
        $country->load($user->getPaypalStringData('oxcountryid'));

        /** @var \OxidSolutionCatalysts\PayPal\Model\State $state */
        $state = oxNew(State::class);
        $state->loadByIdAndCountry(
            $user->getPaypalStringData('oxstateid'),
            $user->getPaypalStringData('oxcountryid')
        );

        /** @var Shop $activeShop */
        $activeShop = Registry::getConfig()->getActiveShop();
        $shopName = $activeShop->getPaypalStringData('oxname');
        $description = sprintf(self::getTranslatedString('OSC_PAYPAL_DESCRIPTION'), $shopName);
        $country_code = '';
        $name                   = $user->getPaypalStringData("oxfname");
        $name                   .= $user->getPaypalStringData("oxlname");
        $locale = '';
        if (
            isset($country->oxcountry__oxisoalpha2)
            && property_exists($country->oxcountry__oxisoalpha2, 'value')
        ) {
            $country_code = $country->oxcountry__oxisoalpha2->value;
            $locale = strtolower($country_code) . '-' . strtoupper($country_code);
        }
        $address                = [
            "address_line_1"    => $user->getPaypalStringData('oxstreet')
                . " " . $user->getPaypalStringData('oxstreetnr'),
            "address_line_2"    => $user->getPaypalStringData('oxcompany')
                . " " . $user->getPaypalStringData('oxaddinfo'),
            "admin_area_1"      => $state->getPaypalStringData('oxtitle'),
            "admin_area_2"      => $user->getPaypalStringData('oxcity'),
            "postal_code"       => $user->getPaypalStringData('oxzip'),
            "country_code"      => $country_code,
        ];
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

    public function createVaultPaymentToken(string $setupToken): array
    {
        $headers = $this->getVaultingHeaders();

        $path = '/v3/vault/payment-tokens';
        $method = 'post';

        $requestBody = [
            "payment_source" => [
                "token" => [
                    "id"    => $setupToken,
                    "type"  => "SETUP_TOKEN",
                ]
            ]
        ];
        $encoded = json_encode($requestBody);
        $body = is_string($encoded) ? (string)$encoded : '';
        $response = $this->send($method, $path, [], $headers, $body);
        $body = $response->getBody();
        $decode = json_decode((string)$body, true);

        return is_array($decode) ? (array)$decode : [];
    }

    public function getVaultPaymentTokens(string $paypalCustomerId): ?array
    {
        /** @var \OxidSolutionCatalysts\PayPal\Core\Config $viewConf */
        $viewConf = oxNew(ViewConfig::class);
        if (!$viewConf->getIsVaultingActive()) {
            return null;
        }

        $path = '/v3/vault/payment-tokens?customer_id=' . $paypalCustomerId;
        $method = 'get';

        $response = $this->send($method, $path);
        $body = $response->getBody();
        $decode = json_decode((string)$body, true);

        return is_array($decode) ? (array)$decode : null;
    }

    public function getVaultPaymentTokenByIndex(string $paypalCustomerId, string $index): array
    {
        $paymentTokens = $this->getVaultPaymentTokens($paypalCustomerId);

        return $paymentTokens["payment_tokens"][$index] ?? [];
    }

    /**
     * @param $paymentTokenId
     * @return bool
     */
    public function deleteVaultedPayment(string $paymentTokenId): bool
    {
        $path = '/v3/vault/payment-tokens/' . $paymentTokenId;
        $method = 'delete';

        $response = $this->send($method, $path);

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
        return array_merge($headers, $this->getAuthHeaders());
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
