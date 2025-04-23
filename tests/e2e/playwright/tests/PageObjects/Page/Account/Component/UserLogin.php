<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Account\Component;

/**
 * Trait for user login widget in account page
 * @package OxidEsales\Codeception\Page\Component\Footer
 */
trait UserLogin
{
    public $loginUserNameField = '#loginUser';

    public $loginUserPasswordField = '#loginPwd';

    public $loginButton = '#loginButton';
}
