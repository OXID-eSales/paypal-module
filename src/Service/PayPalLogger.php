<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Core\Registry;
use Psr\Log\LoggerInterface;
use Webmozart\PathUtil\Path;

class PayPalLogger
{
    private LoggerInterface $moduleLogger;

    public function __construct(
        LoggerInterface $moduleLogger
    )
    {
        $this->moduleLogger = $moduleLogger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->moduleLogger;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getPayPalLogFilePath(): string
    {
        return Path::join([
            Registry::getConfig()->getLogsDir(),
            'paypal',
            $this->getPayPalLogFileName()
        ]);
    }

    private function getPayPalLogFileName(): string
    {
        return "paypal_" . date("Y-m-d") . ".log";
    }
}