<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * PayPal article class
 *
 * @mixin \OxidEsales\Eshop\Application\Model\Article
 * @property Field $oxarticles__oxisdownloadable
 * @property Field $oxarticles__oxnonmaterial
 */
class Article extends Article_parent
{
    /**
     * Checks if article is buyable.
     *
     * This method is called from different places. Among other things when order emails are rendered.
     * The problem is, If we have bought a "last" article and the article is then sold out, then the
     * stock check leads to an error, because logically there is no article left. This is not noticeable
     * with normal payments, since the e-mail is sent at a time when the order has not yet been finally
     * saved and the stock has not yet been adjusted. With PayPal payments, the order email will be sent
     * later, when the order is saved completely and the stock has changed.
     *
     * @return bool
     */
    public function isBuyable()
    {
        if (Registry::getSession()->getVariable('blDontCheckProductStockForPayPalMails')) {
            return true;
        }
        return parent::isBuyable();
    }

    /**
     * Checks if article is visible
     *
     * This method is called from different places. Among other things when order emails are rendered.
     * The problem is, If we have bought a "last" article and the article is then sold out, then the
     * stock check leads to an error, because logically there is no article left. This is not noticeable
     * with normal payments, since the e-mail is sent at a time when the order has not yet been finally
     * saved and the stock has not yet been adjusted. With PayPal payments, the order email will be sent
     * later, when the order is saved completely and the stock has changed.
     *
     * @return bool
     */
    public function isVisible()
    {
        if (Registry::getSession()->getVariable('blDontCheckProductStockForPayPalMails')) {
            return true;
        }
        return parent::isVisible();
    }

    /**
     * Checks if article is virtual (either "downloadable" or "nonmaterial")
     *
     * @return bool
     */
    public function isVirtualPayPalArticle()
    {
        $bIsDownloadable = false;
        $bIsNonMaterial = false;

        if (
            property_exists($this, 'oxarticles__oxisdownloadable')
            && property_exists($this->oxarticles__oxisdownloadable, 'value')
        ) {
            $bIsDownloadable = $this->oxarticles__oxisdownloadable->value != 0;
        }

        if (
            property_exists($this, 'oxarticles__oxnonmaterial')
            && property_exists($this->oxarticles__oxnonmaterial, 'value')
        ) {
            $bIsNonMaterial = $this->oxarticles__oxnonmaterial->value != 0;
        }

        return ($bIsDownloadable || $bIsNonMaterial);
    }
}
