<?php

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Api\VaultingService;
use OxidSolutionCatalysts\PayPal\Core\Config;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
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
        /** @var VaultingService $vaultingService */
        $vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();
        $vaultingService->clearVaultedTokenCache();
        $this->_aViewData['vaultingUserId'] = oxNew(Config::class)->getUserIdForVaulting();
        $moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);

        if ($moduleSettings->isVaultingAllowedForACDC()) {
            $this->_sThisTemplate = 'modules/osc/paypal/account_vaulting_card.tpl';
        }

        return parent::render();
    }
}
