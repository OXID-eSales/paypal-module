<?php

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\AccountControllerTrait;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

/**
 * user account menu for saving paypal (acdc) for purchase later (vaulting without purchase)
 */
class PayPalVaultingCardController extends AccountController
{
    use ServiceContainer;
    use AccountControllerTrait;

    public function render()
    {
        if (!$this->getUser()) {
            return parent::render();
        }
        $this->_aViewData['vaultingUserId'] = oxNew(Config::class)->getUserIdForVaulting();
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        if ($moduleSettings->isVaultingAllowedForACDC()) {
            $this->_sThisTemplate = '@osc_paypal/frontend/account_vaulting_card';
        }

        return parent::render();
    }
}
