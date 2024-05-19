<?php

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\ViewConfig;
use OxidSolutionCatalysts\PayPal\Traits\RequestDataGetter;
use OxidSolutionCatalysts\PayPal\Traits\TranslationDataGetter;

/**
 * user account menu for saving paypal for purchase later (vaulting without purchase)
 */
class PayPalVaultingCardController extends AccountController
{
    use RequestDataGetter;
    use TranslationDataGetter;

    /**
     * @var string Current class template name.
     */
    // phpcs:ignore PSR2.Classes.PropertyDeclaration
    protected $_sThisTemplate = 'modules/osc/paypal/account_vaulting_card.tpl';

    public function render()
    {
        /** @var ViewConfig $viewConfig */
        $viewConfig = $this->getViewConfig();
        $this->_aViewData['vaultingUserId'] = $viewConfig->getUserIdForVaulting();

        return parent::render();
    }

    public function deleteVaultedPayment(): void
    {
        $paymentTokenId = self::getRequestStringParameter("paymentTokenId", true);
        $vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();
        $string = self::getTranslatedString('OSC_PAYPAL_DELETE_FAILED');
        if (!$vaultingService->deleteVaultedPayment($paymentTokenId)) {
            Registry::getUtilsView()->addErrorToDisplay(
                $string,
                false,
                true
            );
        }
    }
}
