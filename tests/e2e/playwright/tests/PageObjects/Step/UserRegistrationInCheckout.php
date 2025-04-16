<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Step;

use OxidEsales\Codeception\Page\Checkout\UserCheckout;
use OxidEsales\Codeception\Module\Translation\Translator;

/**
 * Class UserRegistrationInCheckout
 * @package OxidEsales\Codeception\Step
 */
class UserRegistrationInCheckout extends Step
{
    /**
     * @param array $userLoginData
     * @param array $userData
     * @param array $addressData
     * @param array $shippingAddressData
     *
     * @return \OxidEsales\Codeception\Page\Checkout\PaymentCheckout
     */
    public function createRegisteredUserInCheckout(
        array $userLoginData,
        array $userData,
        array $addressData,
        array $shippingAddressData = []
    ) {
        $userCheckout = $this->enterRegisteredUserData($userLoginData, $userData, $addressData);

        if (!empty($shippingAddressData)) {
            $userCheckout->openShippingAddressForm()->enterShippingAddressData($shippingAddressData);
        }

        $paymentPage = $userCheckout->goToNextStep();
        $breadCrumbName = Translator::translate("PAY");
        $paymentPage->seeOnBreadCrumb($breadCrumbName);
        return $paymentPage;
    }

    /**
     * @param array $userLogin
     * @param array $userData
     * @param array $addressData
     * @param array $shippingAddressData
     *
     * @return \OxidEsales\Codeception\Page\Checkout\PaymentCheckout
     */
    public function createNotRegisteredUserInCheckout(
        string $userLogin,
        array $userData,
        array $addressData,
        array $shippingAddressData = []
    ) {
        $userCheckout = $this->enterNotRegisteredUserData($userLogin, $userData, $addressData);

        if (!empty($shippingAddressData)) {
            $userCheckout->openShippingAddressForm()->enterShippingAddressData($shippingAddressData);
        }

        $paymentPage = $userCheckout->goToNextStep();
        $breadCrumbName = Translator::translate("PAY");
        $paymentPage->seeOnBreadCrumb($breadCrumbName);
        return $paymentPage;
    }

    /**
     * @param array $userLoginData
     * @param array $userData
     * @param array $addressData
     * @param array $shippingAddressData
     *
     * @return \OxidEsales\Codeception\Page\Checkout\UserCheckout
     */
    public function createNotValidRegisteredUserInCheckout(
        array $userLoginData,
        array $userData,
        array $addressData,
        array $shippingAddressData = []
    ) {
        $I = $this->user;
        $userCheckout = $this->enterRegisteredUserData($userLoginData, $userData, $addressData);

        if (!empty($shippingAddressData)) {
            $userCheckout->openShippingAddressForm()->enterShippingAddressData($shippingAddressData);
        }

        $userCheckout = $userCheckout->clickOnRegisterUserButton();
        $breadCrumbName = Translator::translate("ADDRESS");
        $userCheckout->seeOnBreadCrumb($breadCrumbName);
        $I->see($breadCrumbName, $userCheckout->breadCrumb);

        return $userCheckout;
    }

    /**
     * @param array $userLoginData
     * @param array $userData
     * @param array $addressData
     *
     * @return UserCheckout
     */
    private function enterRegisteredUserData(array $userLoginData, array $userData, array $addressData)
    {
        $userCheckout = new UserCheckout($this->user);
        $userCheckout = $userCheckout->selectOptionRegisterNewAccount();

        $userCheckout->enterUserLoginData($userLoginData)
            ->enterUserData($userData)
            ->enterAddressData($addressData);
        return $userCheckout;
    }

    /**
     * @param array $userLogin
     * @param array $userData
     * @param array $addressData
     *
     * @return UserCheckout
     */
    private function enterNotRegisteredUserData(string $userLogin, array $userData, array $addressData)
    {
        $userCheckout = new UserCheckout($this->user);
        $userCheckout = $userCheckout->selectOptionNoRegistration();

        $userCheckout->enterUserLoginName($userLogin)
            ->enterUserData($userData)
            ->enterAddressData($addressData);
        return $userCheckout;
    }
}
