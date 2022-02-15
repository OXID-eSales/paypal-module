<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Exception;

class RedirectWithMessage extends Redirect
{
    /** @var string */
    private $messageKey;

    /** @var array */
    private $messageParams;

    /**
     * @param string $destination
     * @param string $messageKey
     * @param array $messageParams
     */
    public function __construct(string $destination, string $messageKey, array $messageParams = [])
    {
        parent::__construct($destination);

        $this->messageKey = $messageKey;
        $this->messageParams = $messageParams;
    }

    /**
     * @return string
     */
    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    /**
     * @return array
     */
    public function getMessageParams(): array
    {
        return $this->messageParams;
    }
}
