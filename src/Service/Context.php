<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Core\Registry;
use Symfony\Component\Filesystem\Path;

class Context
{
    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getPayPalLogFilePath(): string
    {
        return Path::join(
            Registry::getConfig()->getLogsDir(),
            'paypal',
            $this->getPayPalLogFileName()
        );
    }

    private function getPayPalLogFileName(): string
    {
        return "paypal_" . date("Y-m-d") . ".log";
    }
}
