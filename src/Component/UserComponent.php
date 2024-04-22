<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Component;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\User;
use OxidSolutionCatalysts\PayPal\Core\Utils\PayPalAddressResponseToOxidAddress;
use OxidSolutionCatalysts\PayPal\Traits\RequestDataGetter;
use OxidSolutionCatalysts\PayPal\Traits\SessionDataGetter;

/**
 * @mixin \OxidEsales\Eshop\Application\Component\UserComponent
 */
class UserComponent extends UserComponent_parent
{
    use SessionDataGetter;
    use RequestDataGetter;

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

    /** @phpstan-ignore-next-line */
    public function login_noredirect() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $return = parent::login_noredirect();
        $redirect = self::getSessionStringVariable('paypalRedirect');
        if (!empty($redirect)) {
            $this->getSession()->deleteVariable('paypalRedirect');
            Registry::getUtils()->redirect($redirect, true, 302);
        }

        return $return;
    }

    public function createPayPalGuestUser(\OxidSolutionCatalysts\PayPalApi\Model\Orders\Order $response): void
    {
        /** @phpstan-ignore-next-line */
        $this->setParent(oxNew('Register'));

        $payer = $response->payer;
        if (isset($payer->email_address)) {
            $this->setRequestParameterByPayPal('lgn_usr', $payer->email_address);
        }
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
        $loginSuccess = false;
        /** @var \OxidSolutionCatalysts\PayPal\Model\User $user */
        $user = oxNew(User::class);
        $payer = $response->payer;
        if (
            isset($payer->email_address) &&
            $loginSuccess = $user->login(
                $payer->email_address,
                '',
                self::getRequestBoolParameter('lgn_cook')
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
