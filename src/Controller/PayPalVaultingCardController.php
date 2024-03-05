<?php

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;

/**
 * user account menu for saving paypal for purchase later (vaulting without purchase)
 */
class PayPalVaultingCardController extends AccountController
{
    /**
     * @var string Current class template name.
     */
    // phpcs:ignore PSR2.Classes.PropertyDeclaration
    protected $_sThisTemplate = 'modules/osc/paypal/account_vaulting_card.tpl';

    public function render()
    {
        $this->_aViewData['vaultingUserId'] = $this->getViewConfig()->getUserIdForVaulting();

        return parent::render();
    }

    public function deleteVaultedPayment()
    {
        $paymentTokenId = Registry::getRequest()->getRequestEscapedParameter("paymentTokenId");
        $vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();

        if (!$vaultingService->deleteVaultedPayment($paymentTokenId)) {
            Registry::getUtilsView()->addErrorToDisplay(
                Registry::getLang()->translateString('OSC_PAYPAL_DELETE_FAILED'),
                false,
                true
            );
        }
    }
}
