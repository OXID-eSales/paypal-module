<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Checkout;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Component\PaymentSummary;
use OxidEsales\Codeception\Page\Page;

class OrderCheckout extends Page
{
    use PaymentSummary;

    public string $URL = '/index.php?cl=order&lang=1';
    public string $breadCrumb = '//div[@class="step step-3 active"]';

    private string $basketItemAmount = '//div[@id="list_cartItem_%s"]/div[2]/div/div';
    private string $basketItemId = '//div[@id="list_cartItem_%s"]//ul[contains(@class,"serial-no")]';
    private string $basketItemTitle = '//div[@id="list_cartItem_%s"]/div[2]/div/div';
    private string $couponInformation = '//div[contains(@class,"list-group-item")]';

    private string $billingAddress = '//div[@id="orderAddress"]/div[@class="card-body"][1]/div';
    private string $deliveryAddress = '//div[@id="orderAddress"]/div[@class="card-body"][2]/div';
    private string $downloadableProductsAgreement = '#oxdownloadableproductsagreement';
    private string $basketItemLabel = '#list_cartItem_%d .basket-item-desc .persparamBox';

    private string $editBillingAddress = '//div[@id="orderAddress"]/h2/form[1]/button';
    private string $editCart = '//div[@id="orderEditCart"]//h4/button';
    private string $editPayment = '//form[@id="orderPayment"]/button';
    private string $editShippingMethod = '//form[@id="orderShipping"]/button';
    private string $paymentMethod = '//div[contains(@class,"card")]/div[@class="card-body"][2]';
    private string $shippingMethod = '//div[contains(@class,"card")]/div[@class="card-body"][1]';
    private string $previousStepLink = '';

    private string $submitOrder = '//button[contains(@class,"btn-highlight")]';
    private string $userRemark = '//h2[contains(text(),"%s")]/following-sibling::div';
    private string $userRemarkHeader = 'h2';
    private string $basketItemTotalPrice = '//div[@id="list_cartItem_%s"]//ul[contains(@class,"unit-price")]';

    public function submitOrder(): self
    {
        $I = $this->user;
        $I->waitForText(Translator::translate('SUBMIT_ORDER'));
        $I->retryClick(Translator::translate('SUBMIT_ORDER'));
        $I->waitForPageLoad();
        return $this;
    }

    public function submitOrderSuccessfully(): ThankYou
    {
        $I = $this->user;
        $I->waitForElementClickable($this->submitOrder);
        $I->retryClick($this->submitOrder);
        $thankYouPage = new ThankYou($I);
        $I->waitForElement($thankYouPage->thankYouPage);
        return $thankYouPage;
    }

    public function confirmDownloadableProductsAgreement(): self
    {
        $I = $this->user;
        $I->retryCheckOption($this->downloadableProductsAgreement);
        $I->seeCheckboxIsChecked($this->downloadableProductsAgreement);
        return $this;
    }

    /**
     * Opens previous page: payment checkout.
     *
     * Does not exist in apex theme
     *
     * @return PaymentCheckout
     */
    public function goToPreviousStep(): PaymentCheckout
    {
        $I = $this->user;
        $I->click($this->previousStepLink);
        $paymentPage = new PaymentCheckout($I);
        $I->waitForElement($paymentPage->breadCrumb);
        return $paymentPage;
    }

    public function editUserAddress(): UserCheckout
    {
        $I = $this->user;
        $I->click($this->editBillingAddress);
        $userPage = new UserCheckout($I);
        $I->waitForElement($userPage->breadCrumb);
        return $userPage;
    }

    public function editPaymentMethod(): PaymentCheckout
    {
        $I = $this->user;
        $I->retryClick($this->editPayment);
        $paymentPage = new PaymentCheckout($I);
        $I->waitForElement($paymentPage->breadCrumb);
        return $paymentPage;
    }

    public function validatePaymentMethod(string $paymentMethod): self
    {
        $this->user->see($paymentMethod, $this->paymentMethod);
        return $this;
    }

    public function editShippingMethod(): PaymentCheckout
    {
        $I = $this->user;
        $I->retryClick($this->editShippingMethod);
        $paymentPage = new PaymentCheckout($I);
        $I->waitForElement($paymentPage->breadCrumb);
        return $paymentPage;
    }

    public function validateShippingMethod(string $shippingMethod): self
    {
        $this->user->see($shippingMethod, $this->shippingMethod);
        return $this;
    }

    public function validateCoupon(string $couponId, string $couponDiscount): self
    {
        $I = $this->user;
        $informationText = sprintf(
            '%s (%s) %s',
            Translator::translate('COUPON'),
            $couponId,
            $couponDiscount
        );
        $I->see($informationText, $this->couponInformation);
        return $this;
    }

    public function editCart(): Basket
    {
        $I = $this->user;
        $I->retryClick($this->editCart);
        $basket = new Basket($I);
        $I->waitForElement($basket->breadCrumb);

        return $basket;
    }

    /**
     * $basketProducts[] = ['id' => productId,
     *                   'title' => productTitle,
     *                   'amount' => productAmount,
     *                   'totalPrice' => productTotalPrice]
     */
    public function validateOrderItems(array $basketProducts): self
    {
        $I = $this->user;
        foreach ($basketProducts as $key => $basketProduct) {
            $itemPosition = (string)++$key;
            $I->see(
                sprintf('%s %s', Translator::translate('PRODUCT_NO'), $basketProduct['id']),
                sprintf($this->basketItemId, $itemPosition)
            );
            $I->see($basketProduct['title'], sprintf($this->basketItemTitle, $itemPosition));
            $I->see((string)$basketProduct['totalPrice'], sprintf($this->basketItemTotalPrice, $itemPosition));
            $I->see((string)$basketProduct['amount'], sprintf($this->basketItemAmount, $itemPosition));
        }
        return $this;
    }

    public function seeOrderItemLabel(string $label, int $item): static
    {
        $I = $this->user;
        $I->see(
            sprintf('%s %s', Translator::translate('LABEL'), $label),
            sprintf($this->basketItemLabel, $item)
        );
        return $this;
    }

    public function dontSeeOrderItemHasLabel(int $item): static
    {
        $I = $this->user;
        $I->dontSeeElement(
            sprintf($this->basketItemLabel, $item)
        );
        return $this;
    }

    /**
     * $userBillAddress = [
     *  "userLoginNameField"
     *  "userUstIDField" => "",
     *  "userMobFonField" => "",
     *  "userPrivateFonField" => "11111111$userId",
     *  "userBirthDateDayField" => rand(10, 28),
     *  "userBirthDateMonthField" => rand(10, 12),
     *  "userBirthDateYearField" => rand(1960, 2000),
     *  "userSalutation" => 'Mrs',
     *  "userFirstName" => "user$userId name_šÄßüл",
     *  "userLastName" => "user$userId last name_šÄßüл",
     *  "companyName" => "user$userId company_šÄßüл",
     *  "street" => "user$userId street_šÄßüл",
     *  "streetNr" => "$userId-$userId",
     *  "ZIP" => "1234$userId",
     *  "city" => "user$userId city_šÄßüл",
     *  "additionalInfo" => "user$userId additional info_šÄßüл",
     *  "fonNr" => "111-111-$userId",
     *  "faxNr" => "111-111-111-$userId",
     *  "countryId" => $userCountry
     *  ];
     */
    public function validateUserBillingAddress(array $userBillAddress): self
    {
        $I = $this->user;
        $addressInfo = $this->convertBillInformationIntoString($userBillAddress);
        $I->assertEquals($I->clearString($addressInfo), $I->clearString($I->grabTextFrom($this->billingAddress)));
        return $this;
    }

    /**
     * $userDelAddress = [
     *  "userSalutation" => 'Mrs',
     *  "userFirstName" => "user$userId name_šÄßüл",
     *  "userLastName" => "user$userId last name_šÄßüл",
     *  "companyName" => "user$userId company_šÄßüл",
     *  "street" => "user$userId street_šÄßüл",
     *  "streetNr" => "$userId-$userId",
     *  "ZIP" => "1234$userId",
     *  "city" => "user$userId city_šÄßüл",
     *  "additionalInfo" => "user$userId additional info_šÄßüл",
     *  "fonNr" => "111-111-$userId",
     *  "faxNr" => "111-111-111-$userId",
     *  "countryId" => $userCountry
     *  ];
     */
    public function validateUserDeliveryAddress(array $userDelAddress): self
    {
        $I = $this->user;
        $addressInfo = $this->convertDeliveryAddressIntoString($userDelAddress);
        $I->assertEquals($I->clearString($addressInfo), $I->clearString($I->grabTextFrom($this->deliveryAddress)));
        return $this;
    }

    public function seeUserDeliveryAddressPart(string $addressPart): self
    {
        $I = $this->user;
        $I->see($addressPart, $this->deliveryAddress);
        return $this;
    }

    public function validateRemarkText(string $userRemarkText): self
    {
        $I = $this->user;
        $I->see(Translator::translate('WHAT_I_WANTED_TO_SAY'), $this->userRemarkHeader);
        $I->see($userRemarkText, sprintf($this->userRemark, Translator::translate('WHAT_I_WANTED_TO_SAY')));
        return $this;
    }

    private function convertBillInformationIntoString(array $userAddress): string
    {
        $transformedAddress = $this->convertAddressArrayIntoString($userAddress);
        $transformedAddress .= Translator::translate('EMAIL') . ' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'userLoginNameField');
        $transformedAddress .= Translator::translate('PHONE') . ' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'fonNr');
        $transformedAddress .= ' | ' . Translator::translate('FAX') . ' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'faxNr');
        $transformedAddress .= Translator::translate('CELLUAR_PHONE') . ' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'userMobFonField');
        $transformedAddress .= Translator::translate('PERSONAL_PHONE') . ' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'userPrivateFonField');
        return $transformedAddress;
    }

    private function convertDeliveryAddressIntoString(array $userAddress): string
    {
        $transformedAddress = $this->convertAddressArrayIntoString($userAddress);
        $transformedAddress .= Translator::translate('PHONE') . ' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'fonNr');
        $transformedAddress .= Translator::translate('FAX') . ' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'faxNr');
        return $transformedAddress;
    }

    private function convertAddressArrayIntoString(array $userAddress): string
    {
        $transformedAddress = $this->getAddressElement($userAddress, 'companyName');
        $transformedAddress .= $this->getAddressElement($userAddress, 'additionalInfo');
        $transformedAddress .= $this->getAddressElement(
            $userAddress,
            'userUstIDField',
            Translator::translate('VAT_ID_NUMBER') . ' '
        );
        $transformedAddress .= $this->getAddressElement($userAddress, 'userSalutation');
        $transformedAddress .= $this->getAddressElement($userAddress, 'userFirstName');
        $transformedAddress .= $this->getAddressElement($userAddress, 'userLastName');
        $transformedAddress .= $this->getAddressElement($userAddress, 'street');
        $transformedAddress .= $this->getAddressElement($userAddress, 'streetNr');
        $transformedAddress .= (isset($userAddress['stateId']) && $userAddress['stateId']) ? 'BE ' : '';
        $transformedAddress .= $this->getAddressElement($userAddress, 'ZIP');
        $transformedAddress .= $this->getAddressElement($userAddress, 'city');
        $transformedAddress .= $this->getAddressElement($userAddress, 'countryId');
        return $transformedAddress;
    }

    private function getAddressElement(array $address, string $element, string $label = ''): string
    {
        return (isset($address[$element]) && $address[$element]) ? $label . $address[$element] . ' ' : '';
    }
}
