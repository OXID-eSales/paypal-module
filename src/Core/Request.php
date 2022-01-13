<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

class Request
{
    /**
     * Retrieves raw post data
     */
    public function getRawPost(): string
    {
        return file_get_contents('php://input');
    }

    /**
     * Retrieves request headers
     */
    public function getHeaders(): array
    {
        return getallheaders();
    }
}
