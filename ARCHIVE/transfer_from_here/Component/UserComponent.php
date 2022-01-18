<?php

/**
 * This file is part of OXID eSales PayPal module.
 *
 * OXID eSales PayPal module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eSales PayPal module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales PayPal module.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2020
 */

namespace OxidProfessionalServices\PayPal\Component;

use OxidEsales\Eshop\Core\Registry;
use OxidProfessionalServices\PayPal\Core\Utils\PayPalAddressResponseToOxidAddress;

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

    public function createPayPalGuestUser(\OxidProfessionalServices\PayPal\Api\Model\Orders\Order $response): void
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
