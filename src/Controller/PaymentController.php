<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\UserRepository;

class PaymentController extends PaymentController_parent
{
    use ServiceContainer;

    public function render()
    {
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        if ($paymentService->isOrderExecutionInProgress()) {
            //order execution is already in progress
            Registry::getUtils()->redirect(
                Registry::getConfig()->getShopSecureHomeURL() . 'cl=order',
                true
            );
        }

        if ($paymentService->getSessionPaymentId() === PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID) {
            $paymentService->removeTemporaryOrder();
        }

        return parent::render();
    }

    /**
     * Template variable getter. Returns paymentlist
     *
     * @return array<array-key, mixed>|object
     */
    public function getPaymentList()
    {
        $paymentList = parent::getPaymentList();
        $payPalDefinitions = PayPalDefinitions::getPayPalDefinitions();
        $actShopCurrency = Registry::getConfig()->getActShopCurrencyObject();
        $userRepository = $this->getServiceFromContainer(UserRepository::class);
        $userCountryIso = $userRepository->getUserCountryIso();

        $paymentListRaw = $paymentList;
        $paymentList = [];
        $payPalHealth = $this->getServiceFromContainer(ModuleSettings::class)->checkHealth();

        /*
         * check:
         * - all none PP-Payments
         * - payPalHealth
         * - currency
         * - country
         * - netto-mode
         */

        foreach ($paymentListRaw as $key => $payment) {
            if (
                !isset($payPalDefinitions[$key]) ||
                (
                    $payPalHealth &&
                    (
                        empty($payPalDefinitions[$key]['currencies']) ||
                        in_array($actShopCurrency->name, $payPalDefinitions[$key]['currencies'], true)
                    ) &&
                    (
                        empty($payPalDefinitions[$key]['countries']) ||
                        in_array($userCountryIso, $payPalDefinitions[$key]['countries'], true)
                    ) &&
                    (
                        $payPalDefinitions[$key]['onlybrutto'] === false ||
                        (
                        !$this->getServiceFromContainer(ModuleSettings::class)->isPriceViewModeNetto()
                        )
                    )
                )
            ) {
                $paymentList[$key] = $payment;
            }
        }

        // check ACDC Eligibility
        if (!$this->getServiceFromContainer(ModuleSettings::class)->isAcdcEligibility()) {
            unset($paymentList[PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID]);
        }

        // check Pui Eligibility
        if (!$this->getServiceFromContainer(ModuleSettings::class)->isPuiEligibility()) {
            unset($paymentList[PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID]);
        }

        return $paymentList;
    }

    /**
     * Template variable getter. Returns error text of payments
     *
     * @return string|array
     */
    public function getPaymentErrorText()
    {
        return Registry::getLang()->translateString(
            $this->_sPaymentErrorText,
            (int)Registry::getLang()->getBaseLanguage(),
            false
        );
    }
}
