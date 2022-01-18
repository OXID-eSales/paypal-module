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

namespace OxidProfessionalServices\PayPal\Model;

use OxidEsales\Eshop\Application\Model\Article as EshopArticle;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidProfessionalServices\PayPal\Controller\Admin\Service\SubscriptionService;

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
