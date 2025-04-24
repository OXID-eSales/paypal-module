<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Component\Header;

use OxidEsales\Codeception\Page\Component\Modal;

/**
 * Trait for account menu widget.
 * @package OxidEsales\Codeception\Page\Component\Header
 */
trait Header
{
    use AccountMenu;
    use SearchWidget;
    use Navigation;
    use MiniBasket;
    use LanguageMenu;
    use CurrencyMenu;
    use Modal;
}
