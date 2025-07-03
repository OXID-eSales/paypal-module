<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Step;

use Codeception\Actor;
use OxidEsales\Codeception\Page\Details\ProductDetails;

/**
 * Class ProductNavigation
 * @package OxidEsales\Codeception\Step
 */
class ProductNavigation extends Step
{
    /**
     * Open product details page.
     *
     * @param string $productId The Id of the product
     *
     * @return ProductDetails
     */
    public function openProductDetailsPage(string $productId)
    {
        $I = $this->user;
        $productDetailsPage = new ProductDetails($I);
        $detailsPageUrl = $productDetailsPage->route($productId);

        $detailsPageUrl = $this->appendParametersToUrl($I, $detailsPageUrl);

        $I->amOnPage($detailsPageUrl);
        return $productDetailsPage;
    }

    /**
     * Append required parameters to the url, like force_sid.
     *
     * @param Actor  $I   Actor
     * @param string $url The url
     *
     * @return string
     */
    private function appendParametersToUrl(Actor $I, string $url): string
    {
        $elementName = 'input[name=force_sid]';
        if ($I->seePageHasElement($elementName) && $I->grabValueFrom($elementName)) {
            $force_sid = $I->grabValueFrom($elementName);
            $url .= '&' . http_build_query(['force_sid' => $force_sid]);
        }

        return $url;
    }
}
