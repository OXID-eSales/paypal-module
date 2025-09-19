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
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\OrderProcessTrackingService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Client;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Service\BaseService;
use Psr\Log\LoggerInterface;

interface VaultingServiceInterface
{
    public function getLogger(): LoggerInterface;

    public function generateUserIdToken(string $payPalCustomerId = ''): array;

    public function createVaultSetupToken(string $paymentTypeId): array;

    public function getPaymentSourceForVaulting(string $paymentSourceId): array;

    public function createVaultPaymentToken(string $setupToken): array;

    public function isVaultedPaymentUsed(
        string $paymentType = PayPalDefinitions::PAYMENT_SOURCE_PAYPAL,
        ?User $user = null
    ): bool;

    public function fetchSelectedVaultedPaymentToken(
        ?User $user = null,
        ?string $id = null
    ): ?array;

    public function getVaultedTokenFromCache(): array;

    public function storeVaultedTokenInCache(array $data): void;

    public function clearVaultedTokenCache(): void;

    public function isVaultingCacheRefreshNeeded(): bool;

    public function getVaultPaymentTokens(string $paypalCustomerId): array;

    public function getVaultPaymentTokenByIndex(string $paypalCustomerId, string $index): array;

    public function deleteVaultedPayment(string $paymentTokenId): bool;
}
