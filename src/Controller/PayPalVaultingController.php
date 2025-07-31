<?php

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\AccountControllerTrait;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

/**
 * user account menu for saving paypal for purchase later (vaulting without purchase)
 */
class PayPalVaultingController extends AccountController
{
    use ServiceContainer;
    use AccountControllerTrait;

    public function render()
    {
        $this->_aViewData['vaultingUserId'] = oxNew(Config::class)->getUserIdForVaulting();
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);

        if ($moduleSettings->isVaultingAllowedForPayPal()) {
            $this->_sThisTemplate = '@osc_paypal/frontend/account_vaulting_paypal';
        }

        return parent::render();
    }
}
