<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\UserRepository;

class PaymentController extends PaymentController_parent
{
    use ServiceContainer;

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

        // check currency & netto-view-mode & invoice-country
        $paymentListRaw = $paymentList;
        $paymentList = [];

        foreach ($paymentListRaw as $key => $payment) {
            if (
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
            ) {
                $paymentList[$key] = $payment;
            }
        }

        // check if basic config exists
        if (!$this->getServiceFromContainer(ModuleSettings::class)->checkHealth()) {
            $paymentListRaw = $paymentList;
            $paymentList = [];

            foreach ($paymentListRaw as $key => $payment) {
                if (strpos($key, 'oscpaypal') !== false) {
                    continue;
                }
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
}
