<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Account;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Account\Component\AccountNavigation;
use OxidEsales\Codeception\Page\Component\Header\AccountMenu;
use OxidEsales\Codeception\Page\Component\Modal;
use OxidEsales\Codeception\Page\Component\UserForm;
use OxidEsales\Codeception\Page\Page;

/**
 * Class for my-address page
 * @package OxidEsales\Codeception\Page\Account
 */
class UserAddress extends Page
{
    use AccountMenu;
    use AccountNavigation;
    use Modal;
    use UserForm;

    // include url of current page
    public string $URL = '/en/my-address/';

    // include bread crumb of current page
    public string $breadCrumb = '.breadcrumb';

    public $headerTitle = 'h1';

    public $openBillingAddressFormButton = '#userChangeAddress';

    public $userEmail = 'invadr[oxuser__oxusername]';

    public $userPassword = '#user_password';

    public $saveUserAddressButton = '#accUserSaveTop';

    public $billingAddress = '#addressText';

    public $shippingAddress = '//div[@id="shippingAddress"]/div[1]/div[%s]/div/div[1]';

    public $openShipAddressPanel = '#showShipAddress';

    public $shipAddressPanel = '#shippingAddress';

    public $shipAddressForm = '#shippingAddressForm';

    public $openShipAddressForm = '//div[@id="shippingAddress"]/div[%s]//button[contains(@class,"dd-edit-shipping-address")]';

    public $deleteShipAddress = '//div[@id="shippingAddress"]/div[%s]//button[contains(@class,"dd-delete-shipping-address")]';

    public $selectShipAddress = '//div[@id="shippingAddress"]/div[%s]//label[contains(@class,"setToThisShippingAddress")]';

    public $newShipAddressForm = '//div[@class="panel panel-default dd-add-delivery-address"]';

    private $panelsWithShippingAddresses =
        '//div[@id="shippingAddress"]//label[contains(@class,"setToThisShippingAddress")]';

    /**
     * Opens billing address form.
     *
     * @return $this
     */
    public function openUserBillingAddressForm()
    {
        $I = $this->user;
        $I->click($this->openBillingAddressFormButton);
        $I->waitForElementVisible($this->billCountryId);
        return $this;
    }

    /**
     * Opens shipping address form.
     *
     * @return $this
     */
    public function openShippingAddressForm()
    {
        $I = $this->user;
        $I->retryClick($this->openShipAddressPanel);
        $I->waitForElementVisible($this->shipAddressPanel);
        $I->dontSeeCheckboxIsChecked($this->openShipAddressPanel);
        return $this;
    }

    /**
     * Opens empty form for creating new shipping address.
     *
     * @return $this
     */
    public function selectNewShippingAddress()
    {
        $I = $this->user;
        $I->click($this->newShipAddressForm);
        $I->waitForElementVisible($this->shipAddressForm);
        return $this;
    }

    /**
     * Selects existing shipping address.
     *
     * @param int $position The position of the Address
     *
     * @return $this
     */
    public function selectShippingAddress(int $position)
    {
        $I = $this->user;
        $selectAddressBtn = sprintf($this->selectShipAddress, $position);
        $I->waitForElementClickable($selectAddressBtn);
        $I->retryClick($selectAddressBtn);
        $openFormBtn = sprintf($this->openShipAddressForm, $position);
        $I->waitForElementClickable($openFormBtn);
        $I->retryClick($openFormBtn);
        $I->waitForPageLoad();
        $I->waitForElementVisible($this->shipAddressForm);
        return $this;
    }

    /**
     * @param int $position The position of the Address
     * @return $this
     */
    public function deleteShippingAddress(int $position)
    {
        $I = $this->user;
        $selectBtn = sprintf($this->selectShipAddress, $position);
        $I->waitForElementClickable($selectBtn);
        $I->click($selectBtn);
        $deleteBtn = sprintf($this->deleteShipAddress, $position);
        $I->waitForElementClickable($deleteBtn);
        $I->retryClick($deleteBtn);
        $this->confirmShippingAddressDeletion($position);
        return $this;
    }

    /**
     * @return $this
     */
    public function saveAddress()
    {
        $I = $this->user;
        $I->retryClick($this->saveUserAddressButton);
        $I->waitForPageLoad();
        return $this;
    }

    /**
     * @param string $newEmail The new email address
     * @param string $password The user password
     *
     * @return $this
     */
    public function changeEmail(string $newEmail, string $password)
    {
        $I = $this->user;
        $I->fillField($this->userEmail, $newEmail);
        $I->waitForPageLoad();
        $I->waitForElementVisible($this->userPassword);
        $I->fillField($this->userPassword, $password);
        return $this->saveAddress();
    }

    /**
     * @param array $userBillAddress
     *
     * @return $this
     */
    public function validateUserBillingAddress(array $userBillAddress)
    {
        $I = $this->user;
        $addressInfo = $this->convertBillInformationIntoString($userBillAddress);
        $I->assertEquals($I->clearString($addressInfo), $I->clearString($I->grabTextFrom($this->billingAddress)));
        return $this;
    }

    /**
     * @param array $userDelAddress
     * @param int   $id
     *
     * @return $this
     */
    public function validateUserDeliveryAddress(array $userDelAddress, int $id = 1)
    {
        $I = $this->user;
        $addressInfo = $this->convertDeliveryAddressIntoString($userDelAddress);
        $selectedShippingAddress = sprintf($this->shippingAddress, $id);
        $I->assertEquals($I->clearString($addressInfo), $I->clearString($I->grabTextFrom($selectedShippingAddress)));
        return $this;
    }

    /**
     * @param int $cnt
     * @return $this
     */
    public function seeNumberOfShippingAddresses(int $cnt): self
    {
        $I = $this->user;
        $I->seeNumberOfElements($this->panelsWithShippingAddresses, $cnt);
        return $this;
    }

    /**
     * Forms a string from billing address information array.
     *
     * @param array $userAddress
     *
     * @return string
     */
    private function convertBillInformationIntoString(array $userAddress)
    {
        $transformedAddress = $this->convertAddressArrayIntoString($userAddress);
        $transformedAddress .= Translator::translate('EMAIL') . ' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'userLoginNameField');
        $transformedAddress .= Translator::translate('PHONE') . ' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'fonNr');
        $transformedAddress .= ' | ' . Translator::translate('FAX').' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'faxNr');
        $transformedAddress .= Translator::translate('CELLUAR_PHONE') . ' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'userMobFonField');
        $transformedAddress .= Translator::translate('PERSONAL_PHONE') . ' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'userPrivateFonField');
        return $transformedAddress;
    }

    /**
     * Forms a string from delivery address information array.
     *
     * @param array $userAddress
     *
     * @return string
     */
    private function convertDeliveryAddressIntoString(array $userAddress)
    {
        $transformedAddress = $this->convertAddressArrayIntoString($userAddress);
        $transformedAddress .= Translator::translate('PHONE') . ' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'fonNr');
        $transformedAddress .= Translator::translate('FAX') . ' ';
        $transformedAddress .= $this->getAddressElement($userAddress, 'faxNr');
        return $transformedAddress;
    }

    /**
     * Forms a string from address information array.
     *
     * @param array $userAddress
     *
     * @return string
     */
    private function convertAddressArrayIntoString(array $userAddress)
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

    /**
     * Returns address element value if is set.
     *
     * @param array  $address
     * @param string $element
     * @param string $label
     *
     * @return string
     */
    private function getAddressElement($address, $element, $label = '')
    {
        return (isset($address[$element])) ? $label . $address[$element] . ' ' : '';
    }
}
