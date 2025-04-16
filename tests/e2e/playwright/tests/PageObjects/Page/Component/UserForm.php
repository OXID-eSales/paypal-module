<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Component;

trait UserForm
{
    // include form fields of current page
    public $userLoginNameField = ['id' => 'userLoginName'];
    public $userNameField = 'invadr[oxuser__oxusername]';
    public $userPasswordField = ['id' => 'userPassword'];
    public $userPasswordConfirmField = ['id' => 'userPasswordConfirm'];

    //user data
    public $userUstIDField = 'invadr[oxuser__oxustid]';
    public $userMobFonField = 'invadr[oxuser__oxmobfon]';
    public $userPrivateFonField = 'invadr[oxuser__oxprivfon]';
    public $userBirthDateDayField = 'invadr[oxuser__oxbirthdate][day]';
    public $userBirthDateMonthField = "//select[@id='oxMonth']";
    public $userBirthDateYearField = 'invadr[oxuser__oxbirthdate][year]';

    //user address data
    public $billUserSalutation = '//select[@id="invadr_oxuser__oxfname"]';
    public $billUserFirstName = 'invadr[oxuser__oxfname]';
    public $billUserLastName = 'invadr[oxuser__oxlname]';
    public $billCompanyName = 'invadr[oxuser__oxcompany]';
    public $billStreetNr = 'invadr[oxuser__oxstreetnr]';
    public $billStreet = 'invadr[oxuser__oxstreet]';
    public $billZIP = 'invadr[oxuser__oxzip]';
    public $billCity = 'invadr[oxuser__oxcity]';
    public $billAdditionalInfo = 'invadr[oxuser__oxaddinfo]';
    public $billFonNr = 'invadr[oxuser__oxfon]';
    public $billFaxNr = 'invadr[oxuser__oxfax]';
    public $billCountryId = "//select[@id='invCountrySelect']";
    public $billStateId = "//select[@id='oxStateSelect_invadr[oxuser__oxstateid]']";

    //user delivery address data
    public $delUserSalutation = '//select[@id="deladr_oxaddress__oxsal"]';
    public $delUserFirstName = 'deladr[oxaddress__oxfname]';
    public $delUserLastName = 'deladr[oxaddress__oxlname]';
    public $delCompanyName = 'deladr[oxaddress__oxcompany]';
    public $delStreetNr = 'deladr[oxaddress__oxstreetnr]';
    public $delStreet = 'deladr[oxaddress__oxstreet]';
    public $delZIP = 'deladr[oxaddress__oxzip]';
    public $delCity = 'deladr[oxaddress__oxcity]';
    public $delAdditionalInfo = 'deladr[oxaddress__oxaddinfo]';
    public $delFonNr = 'deladr[oxaddress__oxfon]';
    public $delFaxNr = 'deladr[oxaddress__oxfax]';
    public $delCountryId = "//select[@id='delCountrySelect']";
    public $delStateId = "//select[@id='oxStateSelect_deladr[oxaddress__oxstateid]']";

    public $dropdownMenu = '[role=menu]';
    public $nextOpenedDropdownMenu = '//following::div[contains(@class, "dropdown-menu") and contains(@class, "open")]';

    /**
     * @param string $userLoginName
     *
     * @return $this
     */
    public function enterUserLoginName(string $userLoginName)
    {
        $I = $this->user;
        $I->fillField($this->userLoginNameField, $userLoginName);
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function modifyUserName(string $name): self
    {
        $this->user->fillField($this->userNameField, $name);
        return $this;
    }

    /**
     * Fill fields with user login data.
     * $userData = [
     * 'userLoginNameField',
     * 'userPasswordField'
     * ]
     *
     * @param array $userData
     *
     * @return $this
     */
    public function enterUserLoginData(array $userData)
    {
        $I = $this->user;
        $I->fillField($this->userLoginNameField, $userData['userLoginNameField']);
        $I->fillField($this->userPasswordField, $userData['userPasswordField']);
        $I->fillField($this->userPasswordConfirmField, $userData['userPasswordField']);
        return $this;
    }

    /**
     * Fill fields with user information data.
     * $userData = [
     * 'userUstIDField',
     * 'userMobFonField',
     * 'userPrivateFonField',
     * 'userBirthDateDayField',
     * 'userBirthDateYearField',
     * 'userBirthDateMonthField'
     * ]
     *
     * @param array $userData
     *
     * @return $this
     */
    public function enterUserData(array $userData)
    {
        $I = $this->user;
        $I->waitForPageLoad();
        $I->fillField($this->userUstIDField, $userData['userUstIDField']);
        $I->fillField($this->userMobFonField, $userData['userMobFonField']);
        $I->fillField($this->userPrivateFonField, $userData['userPrivateFonField']);

        $I->fillField($this->userBirthDateDayField, $userData['userBirthDateDayField']);
        $I->fillField($this->userBirthDateYearField, $userData['userBirthDateYearField']);

        $I->selectOption($this->userBirthDateMonthField, $userData['userBirthDateMonthField']);
        return $this;
    }

    /**
     * Fill fields with user billing address data.
     * $userData = [
     *  "userSalutation",
     *  "userFirstName",
     *  "userLastName",
     *  "companyName",
     *  "street",
     *  "streetNr",
     *  "ZIP",
     *  "city",
     *  "additionalInfo",
     *  "fonNr",
     *  "faxNr",
     *  "countryId",
     * ]
     *
     * @param array $userData
     *
     * @return $this
     */
    public function enterAddressData(array $userData)
    {
        $I = $this->user;
        $I->waitForPageLoad();
        $this->selectBillingAddressSalutation($userData['userSalutation']);
        $this->selectBillingCountry($userData['countryId']);
        if (isset($userData['stateId'])) {
            $this->selectBillingAddressState($userData['stateId']);
        }

        foreach ($this->removeSelectFieldsFromUserData($userData) as $textField => $value) {
            $locatorName = 'bill' . ucwords($textField);
            $I->fillField($this->{$locatorName}, $value);
        }
        return $this;
    }

    /**
     * @param string $country
     *
     * @return $this
     */
    public function selectBillingCountry(string $country)
    {
        $I = $this->user;
        $I->selectOption($this->billCountryId, $country);
        $this->waitForDropdownNotVisible($this->billCountryId);
        return $this;
    }

    /**
     * @param string $country
     *
     * @return $this
     */
    public function selectShippingCountry(string $country)
    {
        $I = $this->user;
        $I->selectOption($this->delCountryId, $country);
        $this->waitForDropdownNotVisible($this->delCountryId);
        return $this;
    }

    /**
     * Fill fields with user shipping address data.
     * $userData = [
     *  "userSalutation",
     *  "userFirstName",
     *  "userLastName",
     *  "companyName",
     *  "street",
     *  "streetNr",
     *  "ZIP",
     *  "city",
     *  "additionalInfo",
     *  "fonNr",
     *  "faxNr",
     *  "countryId",
     * ]
     *
     * @param array $userData
     *
     * @return $this
     */
    public function enterShippingAddressData(array $userData)
    {
        $I = $this->user;
        $I->waitForPageLoad();
        $this->selectShippingAddressSalutation($userData['userSalutation']);
        $this->selectShippingCountry($userData['countryId']);
        if (isset($userData['stateId'])) {
            $this->selectShippingAddressState($userData['stateId']);
        }

        foreach ($this->removeSelectFieldsFromUserData($userData) as $textField => $value) {
            $locatorName = 'del' . ucwords($textField);
            $I->fillField($this->{$locatorName}, $value);
        }
        return $this;
    }

    private function openDropdown(string $dropdownButton): void
    {
        $I = $this->user;
        $I->waitForElement($dropdownButton);
        $I->scrollTo($dropdownButton);
        $I->waitForElementClickable($dropdownButton);
        $I->click($dropdownButton);
        $I->waitForElementClickable(
            $this->getActiveDropdown($dropdownButton)
        );
    }

    private function selectBillingAddressSalutation($userSalutation): void
    {
        $I = $this->user;
        $I->selectOption($this->billUserSalutation, $userSalutation);
        $this->waitForDropdownNotVisible($this->billUserSalutation);
    }

    private function selectBillingAddressState($stateId): void
    {
        $I = $this->user;
        $I->selectOption($this->billStateId, $stateId);
        $this->waitForDropdownNotVisible($this->billStateId);
    }

    private function removeSelectFieldsFromUserData(array $userData): array
    {
        unset($userData['userSalutation'], $userData['countryId'], $userData['stateId']);
        return $userData;
    }

    private function selectShippingAddressSalutation($userSalutation): void
    {
        $I = $this->user;
        $I->selectOption($this->delUserSalutation, $userSalutation);
        $this->waitForDropdownNotVisible($this->delUserSalutation);
    }

    private function selectShippingAddressState($stateId): void
    {
        $I = $this->user;
        $I->selectOption($this->delStateId, $stateId);
        $this->waitForDropdownNotVisible($this->delStateId);
    }

    private function waitForDropdownNotVisible(string $dropdownButton): void
    {
        $I = $this->user;
        $I->waitForElementNotVisible(
            $this->getActiveDropdown($dropdownButton)
        );
    }

    private function getActiveDropdown(string $dropdownButton): string
    {
        return $dropdownButton . $this->nextOpenedDropdownMenu;
    }
}
