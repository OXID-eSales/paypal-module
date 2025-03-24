<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Controller\Admin;

use OxidEsales\EshopCommunity\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Traits\AdminOrderTrait;
use OxidSolutionCatalysts\PayPal\Traits\JsonTrait;

/**
* OrderMain class
*
* @mixin \OxidEsales\Eshop\Application\Controller\Admin\PaymentMain
*/
class PaymentMain extends PaymentMain_parent
{
    use AdminOrderTrait;
    use JsonTrait;

    public function save(): void
    {
        $aParams = Registry::getRequest()->getRequestEscapedParameter("editval");
        if (!$this->isValidDefaultPaymentMethod($aParams)) {
            Registry::getUtilsView()->addErrorToDisplay(
                'OSC_PAYPAL_PAYMENT_METHOD_CANNOT_BE_DEFAULT',
                true,
                true
            );
            return;
        }

        parent::save();
    }

    protected function isValidDefaultPaymentMethod(array $aParams): bool
    {
        return (
            isset($aParams['oxpayments__oxchecked'])
            && $aParams['oxpayments__oxchecked'] === '1'
            && $aParams['oxpayments__oxid'] !== PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID
        );
    }
}
