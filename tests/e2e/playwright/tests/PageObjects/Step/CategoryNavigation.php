<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Step;

use Codeception\Actor;
use OxidEsales\Codeception\Page\Details\CategoryDetails;

class CategoryNavigation extends Step
{
    /**
     * Open product details page.
     *
     * @param string $categoryId The Id of the category
     *
     * @return CategoryDetails
     */
    public function openCategoryDetailsPage(string $categoryId)
    {
        $I = $this->user;

        $categoryPage = new CategoryDetails($I);
        $categoryPageUrl = $categoryPage->route($categoryId);

        $categoryPageUrl = $this->appendParametersToUrl($I, $categoryPageUrl);

        $I->amOnPage($categoryPageUrl);

        return $categoryPage;
    }

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
