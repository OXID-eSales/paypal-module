<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Details;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Page;
use OxidEsales\EshopCommunity\Tests\Codeception\Support\AcceptanceTester;

/**
 * Class for the product details page
 *
 * @package OxidEsales\Codeception\Page\Details
 */
class CategoryDetails extends Page
{
    public string $categoryTitle = '//div[@class="container-xxl"]//h1[@class="h2"]';
    public string $categoryDesc = '//div[@id="catDescLocator"]';
    public string $categoryLongDesc = '//div[@id="catLongDescLocator"]';
    public string $subCategoryItem = '//div[contains(@class,"cat-list")]//a[@class="cat-list-item"][%d]';
    public string $subCategoryItemImage = '//div[contains(@class,"cat-list")]//a[@class="cat-list-item"][%d]//img[@class="cat-list-item-img"]';

    /**
     * @param mixed $params The category Id.
     */
    public function route(mixed $params): string
    {
        return $this->URL . '/index.php?' . http_build_query(['cl' => 'alist', 'cnid' => $params]);
    }

    /**
     * $categoryData = ['title', 'description', 'longDescription']
     */
    public function seeCategoryData(array $categoryData): self
    {
        $I = $this->user;
        $I->see($categoryData['title']);
        $I->see($categoryData['description'], $this->categoryDesc);
        $I->see($categoryData['longDescription'], $this->categoryLongDesc);

        return $this;
    }

    /**
     * $subCategoryData = ['title', 'description', 'longDescription']
     */
    public function seeSubCategoryData(array $subCategoryData, int $itemId = 1): self
    {
        $I = $this->user;

        $subcategoryItemSelector = $this->getFormattedSelector(template: $this->subCategoryItem, itemId: $itemId);
        $subcategoryImageSelector = $this->getFormattedSelector(template: $this->subCategoryItemImage, itemId: $itemId);

        $I->see($subCategoryData['title'], $subcategoryItemSelector);

        $this->assertElementAttribute(
            I: $I,
            selector: $subcategoryItemSelector,
            attribute: 'title',
            expectedValue: $subCategoryData['description'],
            message: "The subcategory title attribute does not match the expected description."
        );

        $this->assertElementAttribute(
            I: $I,
            selector: $subcategoryImageSelector,
            attribute: 'alt',
            expectedValue: sprintf(Translator::translate('CATEGORY_IMAGE_ALT'), $subCategoryData['title']),
            message: "The subcategory image alt attribute does not match the expected value."
        );

        return $this;
    }

    private function assertElementAttribute(
        AcceptanceTester $I,
        string $selector,
        string $attribute,
        string $expectedValue,
        string $message = ''
    ): void {
        $actualValue = $I->grabAttributeFrom($selector, $attribute);
        $I->assertSame($expectedValue, $actualValue, $message);
    }

    private function getFormattedSelector(string $template, int $itemId): string
    {
        return sprintf($template, $itemId);
    }
}
