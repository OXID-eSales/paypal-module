<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Registry;

/**
 * VatOptionsService
 *
 * Centralizes access to VAT-related options coming from both the shop configuration
 * and (optionally) the PayPal module configuration.
 *
 * Notes:
 * - Currently, the VAT-relevant flags are standard shop config parameters used by OXID.
 * - A placeholder "module" section is returned for future module-specific VAT settings.
 */
class VatOptionsService
{
    /**
     * Returns a structured array with VAT-related options from shop and module configs.
     * Optionally includes computed values (e.g., current payment VAT amount) if a Basket is provided.
     */
    public function getVatOptions(?Basket $basket = null): array
    {
        $config = Registry::getConfig();

        $shop = [
            'blShowVATForPayCharge' => (bool)$config->getConfigParam('blShowVATForPayCharge'),
            'blShowVATForDelivery'  => (bool)$config->getConfigParam('blShowVATForDelivery'),
            'blShowNetPrice'        => $this->isNetPriceMode($basket),
            'dDefaultVAT'           => (float)($config->getConfigParam('dDefaultVAT') ?? 0.0),
        ];

        // Placeholder for future module-specific VAT options
        $module = [
            // e.g., 'someModuleVatFlag' => (bool)$this->getModuleSetting('someModuleVatFlag'),
        ];

        $computed = [];
        if ($basket instanceof Basket) {
            $computed['paymentVatAmount'] = $this->getPaymentVatAmount($basket);
        }

        return [
            'shop' => $shop,
            'module' => $module,
            'computed' => $computed,
        ];
    }

    public function isPaymentVatVisible(): bool
    {
        return (bool)Registry::getConfig()->getConfigParam('blShowVATForPayCharge');
    }

    public function isDeliveryVatVisible(): bool
    {
        return (bool)Registry::getConfig()->getConfigParam('blShowVATForDelivery');
    }

    public function isNetPriceMode(?Basket $basket = null): bool
    {
        $basket = $this->resolveBasket($basket);
        if ($basket instanceof Basket) {
            // User-aware view mode: a B2B module overriding User::isPriceViewModeNetto() takes effect here
            return $basket->isPriceViewModeNetto();
        }

        // Fallback to the shop default when no basket is available
        return (bool)Registry::getConfig()->getConfigParam('blShowNetPrice');
    }

    /**
     * Resolves the basket to operate on: the passed one is the primary path,
     * the session basket the fallback. Returns null if neither is available.
     */
    private function resolveBasket(?Basket $basket = null): ?Basket
    {
        if ($basket instanceof Basket) {
            return $basket;
        }

        try {
            $session = Registry::getSession();
            return $session ? $session->getBasket() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getDefaultVatRate(): float
    {
        return (float)(Registry::getConfig()->getConfigParam('dDefaultVAT') ?? 0.0);
    }

    /**
     * Returns the VAT amount of the current payment costs for the given basket.
     * If no payment costs exist or VAT is not applicable, returns 0.0.
     */
    public function getPaymentVatAmount(Basket $basket): float
    {
        $paymentCost = $basket->getCosts('oxpayment');
        if (!$paymentCost) {
            return 0.0;
        }
        $vat = (float)$paymentCost->getVatValue();
        return $vat > 0 ? $vat : 0.0;
    }

    // In case module VAT options are introduced, this helper can be uncommented and adapted:
    // private function getModuleSetting(string $key)
    // {
    //     /** @var ModuleSettings $moduleSettings */
    //     // $moduleSettings = Registry::get(ModuleSettings::class);
    //     // return $moduleSettings->getSettingValue($key);
    // }
}
