<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Model;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Model\Category;

class CategoryTest extends UnitTestCase
{
    /**
     * The category list comes from the CATEGORY_ constants of the PayPal client's catalog
     * Product model, so its size depends on the client version - check its shape instead.
     */
    public function testGetCategories(): void
    {
        $category = new Category();
        $categories = $category->getCategories();

        $this->assertNotEmpty($categories);
        foreach (array_keys($categories) as $constantName) {
            $this->assertStringStartsWith('CATEGORY_', $constantName);
        }

        $sortedNames = array_keys($categories);
        sort($sortedNames);
        $this->assertSame($sortedNames, array_keys($categories));
    }
}
