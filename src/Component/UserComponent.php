<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Component;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\User;
use OxidSolutionCatalysts\PayPal\Core\Utils\PayPalAddressResponseToOxidAddress;

/**
 * @mixin \OxidEsales\Eshop\Application\Component\UserComponent
 */
class UserComponent extends UserComponent_parent
{
    public function render()
    {
        $return = parent::render();

        $this->getSession()->deleteVariable('paypalRedirect');

        $redirect = Registry::getRequest()->getRequestEscapedParameter('return');
        if ($redirect) {
            $this->getSession()->setVariable('paypalRedirect', $redirect);
        }

        return $return;
    }

    public function login_noredirect() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $return = parent::login_noredirect();
        $redirect = $this->getSession()->getVariable('paypalRedirect');
        if ($redirect) {
            $this->getSession()->deleteVariable('paypalRedirect');
            Registry::getUtils()->redirect($redirect, true, 302);
        }

        return $return;
    }

    public function createPayPalGuestUser(\OxidSolutionCatalysts\PayPalApi\Model\Orders\Order $response)
    {
        $this->setParent(oxNew('Register'));

        $this->setRequestParameterByPayPal('lgn_usr', $response->payer->email_address);
        // Guest users have a blank password
        $password = '';
        $this->setRequestParameterByPayPal('lgn_pwd', $password);
        $this->setRequestParameterByPayPal('lgn_pwd2', $password);

        $invoiceAddress = PayPalAddressResponseToOxidAddress::mapUserInvoiceAddress($response);
        $this->setRequestParameterByPayPal('invadr', $invoiceAddress);

        $this->registerUser();
    }

    /**
     * Sign the customer in via the PayPal-supplied email address, without
     * going through the standard $user->login() / User::onLogin() password
     * path. The express-checkout login is an out-of-band auth event
     * triggered by a server-verified PayPal order — not by a customer
     * supplied password — so it must not pass through the password-check
     * pipeline. Sidestepping that pipeline also closes the path that
     * previously allowed this method to succeed with an empty password
     * by overriding User::onLogin (CVE-XXXX-XXXX, see security bulletin).
     *
     * The lookup is restricted to oxrights = 'user' so that admin
     * accounts cannot be auto-signed-in via a matching email.
     *
     * @param \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order $response
     */
    public function loginPayPalCustomer(\OxidSolutionCatalysts\PayPalApi\Model\Orders\Order $response): bool
    {
        $email = (string)$response->payer->email_address;
        if ($email === '') {
            return false;
        }

        $shopId = Registry::getConfig()->getShopId();
        $userId = (string)DatabaseProvider::getDb()->getOne(
            "SELECT OXID FROM oxuser
             WHERE oxusername = :oxusername
               AND oxrights   = 'user'
               AND oxshopid   = :oxshopid",
            [
                ':oxusername' => $email,
                ':oxshopid'   => $shopId,
            ]
        );
        if ($userId === '') {
            return false;
        }

        $user = oxNew(User::class);
        if (!$user->load($userId)) {
            return false;
        }

        $this->getSession()->setVariable('usr', $user->getId());
        $this->setUser($user);
        $this->setLoginStatus(USER_LOGIN_SUCCESS);

        return true;
    }

    /**
     * @param string $paramName
     * @param mixed $paramValue
     */
    protected function setRequestParameterByPayPal(string $paramName, $paramValue)
    {
        $_POST[$paramName] = $paramValue;
    }
}
