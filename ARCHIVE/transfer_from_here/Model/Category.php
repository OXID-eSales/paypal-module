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

use OxidProfessionalServices\PayPal\Api\Model\Catalog\Product;

class Category
{
    public function getCategories()
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

    public function getTypes()
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
