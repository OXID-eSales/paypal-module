<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidProfessionalServices\PayPal\Tests\Unit\Model\Logger;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidProfessionalServices\PayPal\Model\Category;

class CategoryTest extends UnitTestCase
{
    public function testGetCategories()
    {
        $category = new Category();
        $categories = $category->getCategories();
        $this->assertCount(446, $categories);
    }
}
