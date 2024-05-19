<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\Field;
use OxidSolutionCatalysts\PayPal\Traits\DataGetter;

/**
 * @property Field $oxcountry__oxisoalpha2
 */
class Country extends \OxidEsales\Eshop\Application\Model\Country
{
    use DataGetter;
}
