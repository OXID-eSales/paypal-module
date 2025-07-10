<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Api;

use JsonException;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\State;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidSolutionCatalysts\PayPal\Traits\OrderProcessTrackingTrait;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Service\BaseService;
use Psr\Log\LoggerInterface;

class VaultingService extends BaseService
{
    use ServiceContainer;
    use OrderProcessTrackingTrait;

    public function getLogger(): LoggerInterface
    {
        return $this->getServiceFromContainer(Logger::class);
    }

    public function generateUserIdToken($payPalCustomerId = false): array
    {
        $headers = [];
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $headers['PayPal-Partner-Attribution-Id'] = Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP;

        $params = [
            'response_type' => 'id_token',
            'grant_type' => 'client_credentials',
        ];

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
            $result = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
        } catch (ApiException|JsonException $e) {
            $result = [];
        }

        return is_array($result) ? $result : [];
    }

    /**
     * Request a setup token either for card or for PayPal vaulting
     * @param string $paymentTypeId
     * @return array
     * @throws JsonException
     */
    public function createVaultSetupToken(string $paymentTypeId): array
    {
        $paymentSourceId = PayPalDefinitions::getPaymentSourceRequestName($paymentTypeId);
        if ($paymentSourceId === PayPalDefinitions::PAYMENT_SOURCE_CARD) {
            $requestBody = [
                "payment_source" => [
                    $paymentSourceId => [],
                ]
            ];
        } else {
            $requestBody = $this->getPaymentSourceForVaulting($paymentSourceId);
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
            $result = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
        } catch (ApiException|JsonException $e) {
            $result = [];
        }

        return is_array($result) ? $result : [];
    }

    /**
     * @param string $paymentSourceId
     * @return array
     */
    public function getPaymentSourceForVaulting(string $paymentSourceId): array
    {
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $viewConf = Registry::get(ViewConfig::class);
        $config = Registry::getConfig();
        $user = $viewConf->getUser();

        $country = oxNew(Country::class);
        $country->load($user->getFieldData('oxcountryid'));

        $state = oxNew(State::class);
        $state->loadByIdAndCountry(
            $user->getFieldData('oxstateid'),
            $user->getFieldData('oxcountryid')
        );

        $shopName = $moduleSettings->getShopName();
        $name = $user->getFieldData("oxfname");
        $name .= $user->getFieldData("oxlname");
        $billingAddress = [
            "address_line_1" => $user->getFieldData('oxstreet') . " " . $user->getFieldData('oxstreetnr'),
            "address_line_2" => $user->getFieldData('oxcompany') . " " . $user->getFieldData('oxaddinfo'),
            "admin_area_1" => $state->getFieldData('oxtitle'),
            "admin_area_2" => $user->getFieldData('oxcity'),
            "postal_code" => $user->getFieldData('oxzip'),
            "country_code" => $country->oxcountry__oxisoalpha2->value,
        ];
        $locale =
            strtolower($country->oxcountry__oxisoalpha2->value)
            . '-'
            . strtoupper($country->oxcountry__oxisoalpha2->value);
        $experience_context = [
            "brand_name" => $shopName,
            "locale" => $locale,
            "return_url" => $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizepaypalsession',
            "cancel_url" => $config->getSslShopUrl() . 'index.php?cl=order&fnc=cancelpaypalsession',
            "shipping_preference" => "SET_PROVIDED_ADDRESS"
        ];

        $attributes = [];
        $vaultPaymentOnSuccess = Registry::getRequest()->getRequestParameter("vaultPayment");
        if (filter_var($vaultPaymentOnSuccess, FILTER_VALIDATE_BOOLEAN)) {
            $attributes = [
                "customer" => [
                    "id" => $user->getFieldData("oscpaypalcustomerid")
                ],
                "vault" => [
                    "store_in_vault" => "ON_SUCCESS",
                    "usage_type" => "MERCHANT",
                    "customer_type" => "CONSUMER",
                    "permit_multiple_payment_tokens" => false
                ]
            ];
        }

        if ($paymentSourceId === PayPalDefinitions::PAYMENT_SOURCE_CARD) {
            $paymentSource = [
                $paymentSourceId => [
                    "name" => $name,
                    "billing_address" => $billingAddress,
                    "experience_context" => $experience_context,
                ]
            ];

            $paymentSource[$paymentSourceId]["attributes"] = array_merge($attributes,
                [
                    "verification" => [
                        "method" => "SCA_WHEN_REQUIRED"
                    ]
                ]);

        } else {
            $paymentSource = [
                $paymentSourceId => [
                    "experience_context" => array_merge([
                        'payment_method_preference' => 'UNRESTRICTED'
                    ], $experience_context),
                ]
            ];

            if (!empty($attributes)) {
                $paymentSource[$paymentSourceId]["attributes"] = $attributes;
            }

            if ($paymentSourceId === PayPalDefinitions::PAYMENT_SOURCE_PAYPAL) {
                $paymentSource[$paymentSourceId]["address"] = $billingAddress;
            }
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
                    "id" => $setupToken,
                    "type" => "SETUP_TOKEN",
                ]
            ]
        ];

        $body = '';
        try {
            $response = $this->send('POST', $path, [], $headers, json_encode($requestBody, JSON_THROW_ON_ERROR));
            if ($response) {
                $body = $response->getBody();
            }
            $result = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
        } catch (ApiException|JsonException $e) {
            $result = [];
        }

        return is_array($result) ? $result : [];
    }

    public function fetchSelectedVaultedPaymentToken(
        ?User   $user = null,
        ?string $id = null
    ): ?array
    {
        $vaultedPaymentTokens = [];
        $payPalCustomerId = $user ? $user->getFieldData("oscpaypalcustomerid") : '';
        if (!empty($payPalCustomerId)) {
            $vaultedPaymentTokens = $this->getVaultPaymentTokens($payPalCustomerId)["payment_tokens"];
        }
        $selectedVaultedPaymentTokenId = null === $id ?
            Registry::getSession()->getVariable("selectedVaultedPaymentTokenId") : $id;

        if (is_null($selectedVaultedPaymentTokenId)) {
            return null;
        }

        $vaultPaymentToken = null;
        foreach ($vaultedPaymentTokens as $vaultPaymentToken) {
            if ($vaultPaymentToken["id"] === $selectedVaultedPaymentTokenId) {
                break;
            }
        }

        return is_array($vaultPaymentToken) ? $vaultPaymentToken : null;
    }

    public function getVaultPaymentTokens(string $paypalCustomerId): array
    {
        $viewConf = oxNew(ViewConfig::class);
        if (!$viewConf->getIsVaultingActive()) {
            return [];
        }
        $this->setTrackingId($this->getTrackingId());
        $headers = [];
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $headers['PayPal-Partner-Attribution-Id'] = Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP;

        $path = '/v3/vault/payment-tokens?customer_id=' . $paypalCustomerId;

        $body = '';
        $result = Registry::getSession()->getVariable('payPalPaymentVaultedTokenCache' . $this->getTrackingId());
        if (empty($result)) {
            try {
                $response = $this->sendWithRequestResponseLogging('GET', $path, [], $headers);
                if ($response) {
                    $body = $response->getBody();
                }
                $result = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
                Registry::getSession()->setVariable('payPalPaymentVaultedTokenCache' . $this->getTrackingId(), $result);
            } catch (ApiException|JsonException $e) {
                $this->getServiceFromContainer(Logger::class)
                    ->log('error', __CLASS__ . ' ' . __FUNCTION__ . ' : ' . $e->getMessage());
                $result = [];
            }
        }

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $vaultedPaymentTokens = $result['payment_tokens'];
        $filteredVaultedPaymentTokens = [];
        $uniquePaypalVaultedPaymentSources = [];
        foreach ($vaultedPaymentTokens as $vaultedPaymentToken) {
            foreach ($vaultedPaymentToken["payment_source"] as $paymentType => $paymentSource) {
                if ($paymentType === PayPalDefinitions::PAYMENT_SOURCE_PAYPAL && $moduleSettings->isVaultingAllowedForPayPal()) {
                    $email = $paymentSource["email_address"];
                    $payer_id = $paymentSource["payer_id"];

                    if (!isset($uniquePaypalVaultedPaymentSources[$email])) {
                        $uniquePaypalVaultedPaymentSources[$email] = [];
                    }

                    if (in_array($payer_id, $uniquePaypalVaultedPaymentSources[$email])) {
                        continue;
                    }

                    $uniquePaypalVaultedPaymentSources[$email][] = $payer_id;
                }
                $filteredVaultedPaymentTokens[] = $vaultedPaymentToken;
            }
        }
        $result['payment_tokens'] = $filteredVaultedPaymentTokens;

        return is_array($result) ? $result : [];
    }

    public function getVaultPaymentTokenByIndex(string $paypalCustomerId, string $index): array
    {
        $paymentTokens = $this->getVaultPaymentTokens($paypalCustomerId);

        return $paymentTokens["payment_tokens"][$index] ?: [];
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
