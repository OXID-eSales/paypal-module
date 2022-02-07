<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Exception;

class Redirect extends PayPalException
{
    /** @var string */
    private $destination;

    /**
     * @param string $destination
     */
    public function __construct(string $destination)
    {
        $this->destination = $destination;

        parent::__construct();
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }
}
