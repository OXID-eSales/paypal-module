<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidSolutionCatalysts\PayPalApi\Model\Catalog\Product;

class Category
{
    public function getCategories(): array
    {
        $refl = new \ReflectionClass(Product::class);
        $constants = $refl->getConstants();

        $categoryList = [];

        foreach ($constants as $constant => $value) {
            if (strpos($constant, 'CATEGORY_') !== false) {
                $categoryList[$constant] = $value;
            }
        }

        ksort($categoryList);

        return $categoryList;
    }

    public function getTypes(): array
    {
        $refl = new \ReflectionClass(Product::class);
        $types = $refl->getConstants();

        $typeList = [];

        foreach ($types as $type => $value) {
            if (strpos($type, 'TYPE_') !== false) {
                $typeList[$type] = $value;
            }
        }

        ksort($typeList);

        return $typeList;
    }
}
