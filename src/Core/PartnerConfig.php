<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

class PartnerConfig
{
    use ServiceContainer;

    public function createNonce(): string
    {
        try {
            // throws Exception if it was not possible to gather sufficient entropy.
            $nonce = bin2hex(random_bytes(42));
        } catch (\Exception $e) {
            $nonce = md5(uniqid('', true) . '|' . microtime()) . substr(md5(mt_rand()), 0, 24);
        }

        return $nonce;
    }

    /**
     * @return bool
     */
    public function isSandbox(): bool
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->isSandbox();
    }

    /**
     * This ClientId is public. The only function is to create
     * a basiclly AccessToken,  Which one is needed to generate
     * the request for the merchant ClientId.
     * For this purpose, this ClientId is unencrypted, here as part
     * of this open Source Module
     * this method is private see getTechnicalClientId which respects the sandbox mode for you
     * @return string
     */
    public function getLiveOxidClientId(): string
    {
        return "AQPFC4NJr-nIiumTXvyfHFJLK-RlWAcv9D0zAc4OWiKiQXyXnJZ7Lw1E2h6O2mtceJf5kWflplieijnK";
    }

    /**
     * This ClientId is public. The only function is to create
     * a basiclly AccessToken,  Which one is needed to generate
     * the request for the merchant ClientId and Secret.
     * For this purpose, this ClientId is unencrypted, here as part
     * of this open Source Module
     *
     * @return string
     */
    public function getSandboxOxidClientId(): string
    {
        return "AS-lHBWs8cudxxonSeQ1eRbdn1Nr-7baqAURRNJnIuP-PPQFzFF1XkjDYV3NG3M6O75st2D98DOil4Vd";
    }

    /**
     * This PartnerId is public. The only function is to create
     * a basiclly AccessToken,  Which one is needed to generate
     * the request for the merchant ClientId and Secret.
     * For this purpose, this ClientId is unencrypted, here as part
     * of this open Source Module
     * this method is private see getTechnicalPartnerId which respects the sandbox mode for you
     * @return string
     */
    public function getLiveOxidPartnerId(): string
    {
        return "FULA6AY33UTA4";
    }

    /**
     * This PartnerId is public. The only function is to create
     * a basiclly AccessToken,  Which one is needed to generate
     * the request for the merchant ClientId and Secret.
     * For this purpose, this PartnerId is unencrypted, here as part
     * of this open Source Module
     *
     * @return string
     */
    public function getSandboxOxidPartnerId(): string
    {
        return "LRCHTG6NYPSXN";
    }

    /**
     * This Secret is public. The only function is to create
     * a basiclly AccessToken,  Which one is needed to generate
     * the request for the merchant ClientId and Secret.
     * For this purpose, this ClientId is unencrypted, here as part
     * of this open Source Module
     *
     * @return string
     */
    public function getLiveOxidSecret(): string
    {
        return "ELcHsbqzqmC8wVbndnDxokTnQboMn-HfcJ2tGfWbxJUIAIys0HMqfzbHrev5R--RPd6B2xNWJrddtO9z";
    }

    /**
     * This Secret is public. The only function is to create
     * a basiclly AccessToken,  Which one is needed to generate
     * the request for the merchant ClientId and Secret.
     * For this purpose, this PartnerId is unencrypted, here as part
     * of this open Source Module
     *
     * @return string
     */
    public function getSandboxOxidSecret(): string
    {
        return "EANkP__pSQ25b1cXuO4CrC_KeDc78rKtgUpeEDthejOVjkJV9sv0mfjxM_A4qXyMqbdCIeib0tDfQY_6";
    }

    /**
     * @return string
     */
    public function getTechnicalClientId()
    {
        return $this->isSandbox() ?
            $this->getSandboxOxidClientId()
            : $this->getLiveOxidClientId();
    }

    /**
     * @return string
     */
    public function getTechnicalPartnerId()
    {
        return $this->isSandbox() ?
            $this->getSandboxOxidPartnerId()
            : $this->getLiveOxidPartnerId();
    }

    /**
     * @return string
     */
    public function getTechnicalClientSecret()
    {
        return $this->isSandbox() ?
            $this->getSandboxOxidSecret()
            : $this->getLiveOxidSecret();
    }
}