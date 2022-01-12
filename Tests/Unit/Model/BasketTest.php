<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidProfessionalServices\PayPal\Tests\Unit\Model;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\TestingLibrary\UnitTestCase;

class BasketTest extends UnitTestCase
{
    public function providerHasProductVariantInBasket()
    {
        return [
            'parent'        => [
                [
                    '_variant',
                    '_alternate_variant'
                ],
                '_parent',
                'assertTrue'
            ],
            'variant'       => [
                [
                    '_variant'
                ],
                '_variant',
                'assertFalse'
            ],
            'other_variant' => [
                [
                    '_variant'
                ],
                '_alternate_variant',
                'assertFalse'
            ],
            'has_no_variant' => [
                [
                    '_variant'
                ],
                '_has_no_variant',
                'assertFalse'
            ]
        ];
    }

    /**
     * @param array $toBasket
     * @param string $productId
     * @param string $assertMethod
     *
     * @dataProvider providerHasProductVariantInBasket()
     */
    public function testHasProductVariantInBasket($toBasket, $productId, $assertMethod)
    {
/*
        $this->prepareProducts();

        $product = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
        $product->load($productId);

        $basket = oxNew(\OxidEsales\Eshop\Application\Model\Basket::class);
        foreach($toBasket as $singleProductId) {
            $basket->addToBasket($singleProductId, 1);
        }

        $this->$assertMethod($basket->hasProductVariantInBasket($product));
*/
    }
}
