<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Component;

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

        Registry::getSession()->deleteVariable('paypalRedirect');

        $redirect = Registry::getRequest()->getRequestEscapedParameter('return');
        if ($redirect) {
            Registry::getSession()->setVariable('paypalRedirect', $redirect);
        }

        return $return;
    }

    /* @phpstan-ignore-next-line */
    public function login_noredirect() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $return = parent::login_noredirect();
        $redirect = Registry::getSession()->getVariable('paypalRedirect');
        if ($redirect) {
            Registry::getSession()->deleteVariable('paypalRedirect');
            Registry::getUtils()->redirect($redirect, true, 302);
        }

        return $return;
    }

    public function createPayPalGuestUser(\OxidSolutionCatalysts\PayPalApi\Model\Orders\Order $response): void
    {
        $this->setParent(oxNew('Register')); /* @phpstan-ignore-line */

        $this->setRequestParameterByPayPal('lgn_usr', $response->payer?->email_address);
        // Guest users have a blank password
        $password = '';
        $this->setRequestParameterByPayPal('lgn_pwd', $password);
        $this->setRequestParameterByPayPal('lgn_pwd2', $password);

        $invoiceAddress = PayPalAddressResponseToOxidAddress::mapUserInvoiceAddress($response);
        $this->setRequestParameterByPayPal('invadr', $invoiceAddress);

        $this->registerUser();
    }

    /**
     * @param \OxidSolutionCatalysts\PayPalApi\Model\Orders\Order $response
     */
    public function loginPayPalCustomer(\OxidSolutionCatalysts\PayPalApi\Model\Orders\Order $response): bool
    {
        $user = oxNew(User::class);

        if (
            $loginSuccess = $user->login(
                (string)$response->payer?->email_address,
                '',
                Registry::getRequest()->getRequestParameter('lgn_cook')
            )
        ) {
            $this->setLoginStatus(USER_LOGIN_SUCCESS);
        }

        return $loginSuccess;
    }

    /**
     * @param string $paramName
     * @param mixed $paramValue
     */
    protected function setRequestParameterByPayPal(string $paramName, $paramValue): void
    {
        $_POST[$paramName] = $paramValue;
    }
}
