<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Component\Widget;

use OxidSolutionCatalysts\PayPal\Traits\ArticleDetailsTrait;

/**
 * Class ArticleDetails
 * @mixin \OxidEsales\Eshop\Application\Component\Widget\ArticleDetails
 */
class ArticleDetails extends ArticleDetails_parent
{
    use ArticleDetailsTrait;
}
