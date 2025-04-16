<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Component;

use OxidEsales\Codeception\Module\Translation\Translator;

trait PaymentSummary
{
    private string $summaryText = '//div[contains(@class,"list-group-item")][contains(text(), "%s")]/span';

    public function seeSummaryWrappingNet(string $value): static
    {
        $I = $this->user;
        $I->see($value, sprintf($this->summaryText, Translator::translate('BASKET_TOTAL_WRAPPING_COSTS_NET')));
        return $this;
    }

    public function seeSummaryPaymentNet(string $value): static
    {
        $I = $this->user;
        $I->see($value, sprintf($this->summaryText, Translator::translate('SURCHARGE')));
        return $this;
    }

    public function seeSummaryShippingNet(string $value): static
    {
        $I = $this->user;
        $I->see($value, sprintf($this->summaryText, Translator::translate('SHIPPING_NET')));
        return $this;
    }

    public function seeSummaryNet(string $value): static
    {
        $I = $this->user;
        $I->see($value, sprintf($this->summaryText, Translator::translate('TOTAL_NET')));
        return $this;
    }

    public function seeSummaryWrappingVat(string $value): static
    {
        $I = $this->user;
        $I->see($value, sprintf($this->summaryText, Translator::translate('PLUS_VAT')));
        return $this;
    }

    public function seeSummaryPaymentVat(string $paymentMethodVat, $percentSurchargeTax): static
    {
        $I = $this->user;
        $I->see(
            $paymentMethodVat,
            sprintf(
                $this->summaryText,
                sprintf(
                    Translator::translate('SURCHARGE_PLUS_PERCENT_AMOUNT'),
                    $percentSurchargeTax
                )
            )
        );
        return $this;
    }

    public function seeSummaryVat(string $percent, string $value = ''): static
    {
        $I = $this->user;
        $I->see(
            $value,
            sprintf(
                $this->summaryText,
                sprintf(
                    Translator::translate('VAT_PLUS_PERCENT_AMOUNT'),
                    $percent
                )
            )
        );
        return $this;
    }

    public function seeSummaryWrappingGross(string $value): static
    {
        $I = $this->user;
        $I->see($value, sprintf($this->summaryText, Translator::translate('GIFT_WRAPPING')));
        return $this;
    }

    public function seeSummaryPaymentGross(string $value): static
    {
        $I = $this->user;
        $I->see($value, sprintf($this->summaryText, Translator::translate('PAYMENT_METHOD')));
        return $this;
    }

    public function seeSummaryGiftCardGross(string $value): static
    {
        $I = $this->user;
        $I->see($value, sprintf($this->summaryText, Translator::translate('GREETING_CARD')));
        return $this;
    }

    public function seeSummaryShippingGross(string $value): static
    {
        $I = $this->user;
        $I->see($value, sprintf($this->summaryText, Translator::translate('SHIPPING_COST')));
        return $this;
    }

    public function seeSummaryGross(string $value): static
    {
        $I = $this->user;
        $I->see($value, sprintf($this->summaryText, Translator::translate('TOTAL_GROSS')));
        return $this;
    }

    public function seeSummarySurchargePaymentMethod(string $value): static
    {
        $I = $this->user;
        $surchargePaymentMethod = Translator::translate('SURCHARGE') . ' ' . Translator::translate('PAYMENT_METHOD');
        $I->see($value, sprintf($this->summaryText, $surchargePaymentMethod));
        return $this;
    }

    public function dontSeeSummarySurchargePaymentMethod(): static
    {
        $I = $this->user;
        $surchargePaymentMethod = Translator::translate('SURCHARGE') . ' ' . Translator::translate('PAYMENT_METHOD');
        $I->waitForPageLoad();
        $I->dontsee(sprintf($this->summaryText, $surchargePaymentMethod));
        return $this;
    }

    public function seeSummaryGrandTotal(string $value): static
    {
        $I = $this->user;
        $I->see($value, sprintf($this->summaryText, Translator::translate('GRAND_TOTAL')));
        return $this;
    }
}
