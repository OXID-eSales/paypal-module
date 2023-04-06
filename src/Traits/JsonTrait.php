<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Traits;

use OxidEsales\Eshop\Core\Registry;

trait JsonTrait
{
    /**
     * Encodes and sends response as json
     *
     * @param $response
     */
    protected function outputJson($response)
    {
        $utils = Registry::getUtils();
        $utils->setHeader('Content-Type: application/json');

        $utils->showMessageAndExit(json_encode($response));
    }
}
