<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Application\Model\Article as EshopArticle;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Service\SubscriptionService;

class BasketItem extends BasketItem_parent
{
    public function setPrice($oPrice)
    {
        parent::setPrice($oPrice);

        $basketArticle = $this->getArticle(true);

        // set Price to "0" for subscription-products
        $basketArticle->getId();
        $article = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
        $article->load($basketArticle->getId());
        if ($article->isPayPalProductLinked()) {
            $this->_oUnitPrice->setPrice(0.0);
            $this->_oPrice->setPrice(0.0);
        }
    }
}
