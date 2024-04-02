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
     */
    protected function outputJson(array $response): void
    {
        $utils = Registry::getUtils();
        $utils->setHeader('Content-Type: application/json');

        $sMsg = json_encode($response);
        if(is_string($sMsg)) {
            $utils->showMessageAndExit($sMsg);
        }
    }
}
