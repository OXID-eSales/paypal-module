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
     * @param mixed $response
     * @return void
     */
    protected function outputJson($response): void
    {
        $utils = Registry::getUtils();
        $utils->setHeader('Content-Type: application/json');

        // json_encode can return false on error, so we should handle that case
        $message = json_encode($response);
        if ($message === false) {
            $message = 'wrong response';
        }

        $utils->showMessageAndExit($message);
    }
}
