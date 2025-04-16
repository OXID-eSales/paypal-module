<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Component\Widget;

use Codeception\Actor;
use OxidEsales\Codeception\Page\Component\Header\MiniBasket;
use OxidEsales\Codeception\Page\Page;

class Promotion extends Page
{
    private string $widgetId;

    public function __construct(Actor $I, string $widgetId)
    {
        $this->widgetId = $widgetId;
        parent::__construct($I);
    }

    public function getProduct(int $position): ProductCard
    {
        return new ProductCard($this->user, $this->widgetId, $position);
    }
}
