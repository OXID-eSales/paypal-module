<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidSolutionCatalysts\PayPal\Traits\ArticleDetailsTrait;

/**
 * Class ArticleDetailsController
 * @mixin \OxidEsales\Eshop\Application\Controller\ArticleDetailsController
 */
class ArticleDetailsController extends ArticleDetailsController_parent
{
    use ArticleDetailsTrait;
}
