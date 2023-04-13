<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Model\Logger;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Model\Category;

class CategoryTest extends UnitTestCase
{
    public function testGetCategories()
    {
        $this->markTestIncomplete('TODO');

        $category = new Category();
        $categories = $category->getCategories();
        $this->assertCount(446, $categories);
    }
}
