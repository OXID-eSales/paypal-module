<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Checkout;

use Facebook\WebDriver\WebDriverKeys;
use OxidEsales\Codeception\Page\Component\Header\AccountMenu;
use OxidEsales\Codeception\Page\Component\Header\MiniBasket;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Component\PaymentSummary;
use OxidEsales\Codeception\Page\Page;

class Basket extends Page
{
    use AccountMenu;
    use MiniBasket;
    use PaymentSummary;

    public string $URL = '';
    public string $breadCrumb = '.breadcrumb';
    public string $basketSummary = '//div[contains(text(),"%s")]/span';
    public string $basketItemAmount = '#am_%s';
    public string $basketItemTotalPrice = '//div[@id="list_cartItem_%s"]//div[contains(@class,"totalPrice")]/strong';
    private string $basketItemUnitPrice = '//div[contains(@class, "basketitemrow")][%s]' .
                                          '//ul[contains(@class, "unit-price")]';
    public string $basketItemTitle = '//div[@id="list_cartItem_%s"]//div[@class="h5"]';
    public string $basketItemId = '//div[@id="list_cartItem_%s"]//ul[contains(@class,"serial-no")]';
    public string $basketBundledItemAmount = '//div[@id="list_cartItem_%s"]//div[contains(@class,"quantity")]';
    public string $basketUpdateButton = '//input[@id="am_%s"]/following-sibling::button';
    public string $openBasketCouponField = '//h2[contains(text(),"%s")]';
    public string $addBasketCouponField = '#input_voucherNr';
    public string $addBasketCouponButton = '//div[@id="voucherCollapse"]//button';
    public string $removeBasketCoupon = '//a[@class="btn removeFn py-0"]';
    public string $openGiftSelection = '//div[@id="list_cartItem_%s"]//div[@class="wrapping"]/a';
    public string $basketItemAttributes = '//div[@id="list_cartItem_%s"]//ul[contains(@class,"attributes")]';
    public string $basketItemSelection = '//div[@id="cartItemSelections_%s"]/div';
    public string $checkoutButton = '.content';
    private string $persistentParamInput = '#list_cartItem_%d input.persParam';

    public function updateProductAmount(float $amount, int $itemPosition = 1): self
    {
        $I = $this->user;
        $I->pressKey(sprintf($this->basketItemAmount, $itemPosition), [
            WebDriverKeys::CONTROL, 'a'
        ]);
        $I->pressKey(sprintf($this->basketItemAmount, $itemPosition), WebDriverKeys::BACKSPACE);
        $I->pressKey(
            sprintf($this->basketItemAmount, $itemPosition),
            $amount,
            WebDriverKeys::ENTER
        );
        $I->waitForPageLoad();
        return $this;
    }

    /**
     * $basketProducts[] = ['id' => productId,
     *                   'title' => productTitle,
     *                   'amount' => productAmount,
     *                   'totalPrice' => productTotalPrice]
     */
    public function seeBasketContains(array $basketProducts, string $basketSummaryPrice): self
    {
        $I = $this->user;
        foreach ($basketProducts as $key => $basketProduct) {
            $itemPosition = $key + 1;
            $I->see(Translator::translate('PRODUCT_NO') .
                ' ' . $basketProduct['id'], sprintf($this->basketItemId, $itemPosition));
            $I->see($basketProduct['title'], sprintf($this->basketItemTitle, $itemPosition));
            $I->see($basketProduct['totalPrice'], sprintf($this->basketItemTotalPrice, $itemPosition));
            $I->seeInField(sprintf($this->basketItemAmount, $itemPosition), (string)$basketProduct['amount']);
        }
        $I->see($basketSummaryPrice, sprintf($this->basketSummary, Translator::translate('GRAND_TOTAL')));
        return $this;
    }

    /**
     * $basketProduct = ['id' => productId,
     *                   'title' => productTitle,
     *                   'amount' => productAmount]
     */
    public function seeBasketContainsBundledProduct(array $basketProduct, int $itemPosition): self
    {
        $I = $this->user;
        $I->see(Translator::translate('PRODUCT_NO') .
            ' ' . $basketProduct['id'], sprintf($this->basketItemId, $itemPosition));
        $I->see($basketProduct['title'], sprintf($this->basketItemTitle, $itemPosition));
        $I->see($basketProduct['amount'], sprintf($this->basketBundledItemAmount, $itemPosition));
        return $this;
    }

    public function seeBasketContainsAttribute(string $basketProductAttribute, int $itemPosition)
    {
        $I = $this->user;
        $I->see($basketProductAttribute, sprintf($this->basketItemAttributes, $itemPosition));
        return $this;
    }

    public function seeBasketContainsSelectionList(
        string $selectionListTitle,
        string $selectionListValue,
        int $itemPosition
    ) {
        $I = $this->user;
        $I->see($selectionListTitle . ': ' . $selectionListValue, sprintf($this->basketItemSelection, $itemPosition));
        return $this;
    }

    public function goToNextStep(): UserCheckout
    {
        $I = $this->user;
        $I->retryClick(Translator::translate('CHECKOUT'), $this->checkoutButton);
        $userStep = new UserCheckout($I);
        $I->waitForElement($userStep->breadCrumb);
        return $userStep;
    }

    public function seeNextStep(): self
    {
        $I = $this->user;
        $I->see(Translator::translate('CHECKOUT'));
        return $this;
    }

    public function dontSeeNextStep(): self
    {
        $I = $this->user;
        $I->dontSee(Translator::translate('CHECKOUT'));
        return $this;
    }

    public function addCouponToBasket(string $couponNumber): self
    {
        $I = $this->user;
        $I->click(sprintf($this->openBasketCouponField, Translator::translate('COUPON')));
        $I->waitForElementVisible($this->addBasketCouponField);
        $I->fillField($this->addBasketCouponField, $couponNumber);
        $I->retryClick($this->addBasketCouponButton);
        $I->waitForElementVisible($this->removeBasketCoupon);
        return $this;
    }

    public function removeCouponFromBasket(): self
    {
        $this->user->click($this->removeBasketCoupon);
        return $this;
    }

    public function openGiftSelection(int $itemPosition): GiftSelection
    {
        $I = $this->user;
        $I->retryClick(sprintf($this->openGiftSelection, $itemPosition));
        $I->waitForText(Translator::translate('GIFT_OPTION'));
        return new GiftSelection($I);
    }

    public function seeItemUnitPrice(string $price, string $position): self
    {
        $I = $this->user;
        $I->see($price, sprintf($this->basketItemUnitPrice, $position));
        return $this;
    }

    public function seeProductLabel(string $label, int $item): static
    {
        $I = $this->user;
        $I->seeInField(
            sprintf($this->persistentParamInput, $item),
            $label,
        );

        return $this;
    }

    public function dontSeeProductLabelInput(int $item): static
    {
        $I = $this->user;
        $I->dontSeeElement(
            sprintf($this->persistentParamInput, $item)
        );

        return $this;
    }

    public function addProductLabel(string $label, int $item): static
    {
        $I = $this->user;
        $I->fillField(
            sprintf($this->persistentParamInput, $item),
            $label
        );
        $I->comment('press the key to trigger CHANGE event');
        $I->pressKey(
            sprintf($this->persistentParamInput, $item),
            WebDriverKeys::ENTER,
        );
        $I->waitForAjax();

        return $this;
    }
}
