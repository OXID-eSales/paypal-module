<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Component\Footer;

/**
 * Trait for service menu widget in footer
 * @package OxidEsales\Codeception\Page\Component\Footer
 */
trait Footer
{
    use NewsletterBox;
    use ServiceWidget;
    use ManufacturerWidget;
    use CategoryWidget;
    use InformationWidget;
}
