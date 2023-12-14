<?php

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;

/**
 * user account menu for saving paypal for purchase later (vaulting without purchase)
 */
class PayPalVaultingController extends AccountController
{
    protected $_sThisTemplate = 'modules/osc/paypal/account_vaulting_paypal.tpl';

    public function render()
    {
        $this->_aViewData['vaultingUserId'] = $this->getViewConfig()->getUserIdForVaulting();

        return parent::render();
    }
}