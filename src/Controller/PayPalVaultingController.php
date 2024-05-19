<?php

namespace OxidSolutionCatalysts\PayPal\Controller;

use mysql_xdevapi\Exception;
use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\ViewConfig;
use OxidSolutionCatalysts\PayPal\Traits\RequestDataGetter;

/**
 * user account menu for saving PayPal for purchase later (vaulting without purchase)
 */
class PayPalVaultingController extends AccountController
{
    use RequestDataGetter;

    /**
     * @var string Current class template name.
     */
    // phpcs:ignore PSR2.Classes.PropertyDeclaration
    protected $_sThisTemplate = 'modules/osc/paypal/account_vaulting_paypal.tpl';

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

        if (!$vaultingService->deleteVaultedPayment($paymentTokenId)) {
            $string = Registry::getLang()->translateString('OSC_PAYPAL_DELETE_FAILED');
            $string = !is_string($string) ?: (string)$string;
            if (is_string($string)) {
                Registry::getUtilsView()->addErrorToDisplay(
                    $string,
                    false,
                    true
                );
            }
        }
    }
}
