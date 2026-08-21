<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Api;

use JsonException;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\State;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\LanguageLocaleMapper;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\OrderProcessTrackingService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Client;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Service\BaseService;
use Psr\Log\LoggerInterface;

class VaultingService extends BaseService
{
    use ServiceContainer;

    /** @var OrderProcessTrackingService */
    private $orderProcessTrackingService;

    public function __construct(OrderProcessTrackingService $orderProcessTrackingService, Client $client)
    {
        $this->orderProcessTrackingService = $orderProcessTrackingService;
        parent::__construct($client);
    }

    public function getLogger(): LoggerInterface
    {
        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');
        return $logger;
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
        } catch (ApiException | JsonException $e) {
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
        } catch (ApiException | JsonException $e) {
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
            "address_line_1" => $user->getFieldData('oxstreet')
                . " " . $user->getFieldData('oxstreetnr'),
            "address_line_2" => $user->getFieldData('oxcompany')
                . " " . $user->getFieldData('oxaddinfo'),
            "admin_area_1" => $state->getFieldData('oxtitle'),
            "admin_area_2" => $user->getFieldData('oxcity'),
            "postal_code" => $user->getFieldData('oxzip'),
            "country_code" => $country->oxcountry__oxisoalpha2->value,
        ];
        // the locale is the language PayPal talks to the customer in, so it is resolved from the shop
        // language against the configured oscPayPalLocales - deriving it from the country alone used
        // to produce invalid values like "ch-CH" for any country whose iso code is not a language
        $locale = $this->getServiceFromContainer(LanguageLocaleMapper::class)
            ->mapLanguageToBcp47Locale(
                (string)Registry::getLang()->getLanguageAbbr(),
                (string)$country->oxcountry__oxisoalpha2->value
            );
        $experience_context = [
            "brand_name" => $shopName,
            "return_url" => $config->getSslShopUrl() . 'index.php?cl=order&fnc=finalizepaypalsession',
            "cancel_url" => $config->getSslShopUrl() . 'index.php?cl=order&fnc=cancelpaypalsession',
            "shipping_preference" => "SET_PROVIDED_ADDRESS"
        ];
        if ($locale !== '') {
            $experience_context["locale"] = $locale;
        }

        $attributes = [];
        $vaultPaymentOnSuccess = Registry::getRequest()->getRequestParameter("vaultPayment");
        $vaultPaymentOnSuccess = filter_var($vaultPaymentOnSuccess, FILTER_VALIDATE_BOOLEAN);
        if ($vaultPaymentOnSuccess) {
            $customerId = $user->getFieldData("oscpaypalcustomerid");
            $attributes = [
                "vault" => [
                    "store_in_vault" => "ON_SUCCESS",
                ]
            ];

            if (!empty($customerId)) {
                $attributes['customer'] = [
                        "id" => $customerId
                ];
            }

            if ($paymentSourceId === PayPalDefinitions::PAYMENT_SOURCE_PAYPAL) {
                $attributes['vault'] += [
                    "usage_type" => "MERCHANT",
                    "customer_type" => "CONSUMER",
                    "permit_multiple_payment_tokens" => false
                ];
            }
        }

        if ($paymentSourceId === PayPalDefinitions::PAYMENT_SOURCE_CARD) {
            $paymentSource = [
                $paymentSourceId => [
                    "name" => $name,
                    "billing_address" => $billingAddress,
                    "experience_context" => $experience_context,
                ]
            ];

            $paymentSource[$paymentSourceId]["attributes"] = array_merge(
                $attributes,
                [
                    "verification" => [
                        "method" => $moduleSettings->getPayPalSCAContingency()
                    ]
                ]
            );

            // only in vaulting-mode
            if ($vaultPaymentOnSuccess) {
                $paymentSource[$paymentSourceId]["stored_credential"] = [
                    "payment_initiator" => "CUSTOMER",
                    "payment_type" => "ONE_TIME",
                    "usage" => "FIRST"
                ];
            }
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
            $response = $this->send(
                'POST',
                $path,
                [],
                $headers,
                json_encode($requestBody, JSON_THROW_ON_ERROR)
            );

            if ($response) {
                $body = $response->getBody();
            }
            $result = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
        } catch (ApiException | JsonException $e) {
            $result = [];
        }

        return is_array($result) ? $result : [];
    }

    /**
     * Check if a specific type of vaulted payment is used
     *
     * @param string $paymentType The payment type to check (PayPalDefinitions::PAYMENT_SOURCE_PAYPAL or 'card')
     * @param ?User $user The user to check
     * @return bool True if the specified vaulted payment is used
     */
    public function isVaultedPaymentUsed(
        string $paymentType = PayPalDefinitions::PAYMENT_SOURCE_PAYPAL,
        ?User $user = null
    ): bool {
        $payPalCustomerId = $user ? $user->getFieldData("oscpaypalcustomerid") : '';
        if (empty($payPalCustomerId)) {
            return false;
        }

        $vaultedPaymentTokens = $this->getVaultPaymentTokens($payPalCustomerId)["payment_tokens"] ?? [];

        if ($paymentType === PayPalDefinitions::PAYMENT_SOURCE_PAYPAL) {
            // Check for PayPal payment source
            return !empty(array_filter($vaultedPaymentTokens, function ($token) {
                return isset($token["payment_source"][PayPalDefinitions::PAYMENT_SOURCE_PAYPAL]);
            }));
        } else {
            // Check for card payment source
            $vaultedPaymentTokenSelected = $this->fetchSelectedVaultedPaymentToken($user);
            if (empty($vaultedPaymentTokenSelected)) {
                return false;
            }

            return in_array($vaultedPaymentTokenSelected['id'], array_column($vaultedPaymentTokens, 'id'));
        }
    }

    public function fetchSelectedVaultedPaymentToken(
        ?User $user = null,
        ?string $id = null
    ): ?array {
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

    /**
     * Get the cache key for vaulted tokens
     *
     * @return string
     */
    protected function getVaultedTokenCacheKey(): string
    {
        $trackingId = $this->orderProcessTrackingService->getTrackingId();
        return 'payPalPaymentVaultedTokenCache' . $trackingId;
    }

    /**
     * Get vaulted token data from cache
     *
     * @return array
     */
    public function getVaultedTokenFromCache(): array
    {
        $trackingId = $this->orderProcessTrackingService->getTrackingId();
        if (empty($trackingId)) {
            return [];
        }

        $cacheKey = $this->getVaultedTokenCacheKey();
        return Registry::getSession()->getVariable($cacheKey) ?: [];
    }

    /**
     * Store vaulted token data in cache
     *
     * @param array $data
     * @return void
     */
    public function storeVaultedTokenInCache(array $data): void
    {
        $trackingId = $this->orderProcessTrackingService->getTrackingId();
        if (empty($trackingId)) {
            return;
        }

        $cacheKey = $this->getVaultedTokenCacheKey();
        Registry::getSession()->setVariable($cacheKey, $data);
    }

    /**
     * Clear vaulted token cache
     *
     * @return void
     */
    public function clearVaultedTokenCache(): void
    {
        $trackingId = $this->orderProcessTrackingService->getTrackingId();
        if (empty($trackingId)) {
            return;
        }

        $cacheKey = $this->getVaultedTokenCacheKey();
        Registry::getSession()->deleteVariable($cacheKey);
    }

    /**
     * Check if vaulting cache refresh is needed
     *
     * @return bool True if refresh is needed, false otherwise
     */
    public function isVaultingCacheRefreshNeeded(): bool
    {
        return !$this->orderProcessTrackingService->isPaymentProcessStarted();
    }

    public function getVaultPaymentTokens(string $paypalCustomerId): array
    {
        $viewConf = oxNew(ViewConfig::class);
        if (!$viewConf->getIsVaultingActive()) {
            return [];
        }
        $currentTrackingId = (string)Registry::getSession()->getVariable('payPalPaymentProcessId');
        $this->orderProcessTrackingService->setTrackingId($currentTrackingId);
        $this->setTrackingId($currentTrackingId);
        $headers = [];
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $headers['PayPal-Partner-Attribution-Id'] = Constants::PAYPAL_PARTNER_ATTRIBUTION_ID_PPCP;

        $path = '/v3/vault/payment-tokens?customer_id=' . $paypalCustomerId;

        $body = '';
        $cachedResult = $this->getVaultedTokenFromCache();

        // If we're in the middle of a payment process, use cached results if available
        // Is we're outside a payment process, or cache is empty, fetch fresh results
        if (empty($cachedResult) || $this->isVaultingCacheRefreshNeeded()) {
            try {
                $response = $this->sendWithRequestResponseLogging('GET', $path, [], $headers);
                $body = $response->getBody();
                $result = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
                $this->storeVaultedTokenInCache($result);
            } catch (ApiException | JsonException $e) {
                $this->getLogger()
                    ->log('warning', 'Vaulted token fetch failed, falling back to cached result (' . __CLASS__ . '::' . __FUNCTION__ . '): ' . $e->getMessage());
                $result = $cachedResult ?: [];
            }
        } else {
            $result = $cachedResult;
        }

        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $vaultedPaymentTokens = $result['payment_tokens'];
        $filteredVaultedPaymentTokens = [];
        $uniquePaypalVaultedPaymentSources = [];
        foreach ($vaultedPaymentTokens as $vaultedPaymentToken) {
            foreach ($vaultedPaymentToken["payment_source"] as $paymentType => $paymentSource) {
                if (
                    $paymentType === PayPalDefinitions::PAYMENT_SOURCE_PAYPAL
                    && $moduleSettings->isVaultingAllowedForPayPal()
                ) {
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
