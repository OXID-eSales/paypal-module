<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Step;

/**
 * Class Step
 * @package OxidEsales\Codeception\Step
 */
class Step
{
    /**
     * @var \Codeception\Actor
     */
    protected $user;

    /**
     * Step constructor.
     * @param \Codeception\Actor $I
     */
    public function __construct(\Codeception\Actor $I)
    {
        $this->user = $I;
    }
}
