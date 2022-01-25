<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Component;

use OxidEsales\Eshop\Core\Registry;
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

    public function login_noredirect()
    {
        $return = parent::login_noredirect();
        $redirect = $this->getSession()->getVariable('paypalRedirect');
        if ($redirect) {
            $this->getSession()->deleteVariable('paypalRedirect');
            Registry::getUtils()->redirect($redirect, true, 302);
        }

        return $return;
    }

    public function createPayPalGuestUser(\OxidSolutionCatalysts\PayPalApi\Model\Orders\Order $response): void
    {
        $this->setParent(oxNew('Register'));

        $this->setRequestParameter('lgn_usr', $response->payer->email_address);
        // Guest users have a blank password
        $password = '';
        $this->setRequestParameter('lgn_pwd', $password);
        $this->setRequestParameter('lgn_pwd2', $password);
        $this->setRequestParameter('lgn_pwd2', $password);

        $invoiceAddress = PayPalAddressResponseToOxidAddress::mapAddress($response, 'oxuser__');
        $deliveryAddress = PayPalAddressResponseToOxidAddress::mapAddress($response, 'oxaddress__');
        $this->setRequestParameter('invadr', $invoiceAddress);
        $this->setRequestParameter('deladr', $deliveryAddress);

        $this->registerUser();
    }

    /**
     * @param string $paramName
     * @param mixed $paramValue
     */
    protected function setRequestParameter(string $paramName, $paramValue): void
    {
        $_POST[$paramName] = $paramValue;
    }
}
