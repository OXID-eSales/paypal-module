<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Core\Registry;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Service Logger
 */
class Logger implements LoggerInterface
{
    /** @var PsrLoggerInterface $moduleLogger */
    private $moduleLogger;

    public function __construct(
        PsrLoggerInterface $moduleLogger
    ) {
        $this->moduleLogger = $moduleLogger;
    }

    /** @var array $possiblePayPalLevels */
    private $possiblePayPalLevels = [
        'error' => 400,
        'info'  => 200,
        'debug' => 100
    ];

    public function log(string $level, string $message, array $exception = []): void
    {
        if ($this->isLogLevel($level)) {
            $this->moduleLogger->$level($message, $exception);
        }
    }

    public function isLogLevel(string $level): bool
    {
        $logLevel = Registry::getConfig()->getConfigParam('sLogLevel') ?? 'error';
        $logLevel = isset($this->possiblePayPalLevels[$logLevel]) ? $logLevel : 'error';
        $level = isset($this->possiblePayPalLevels[$level]) ? $level : 'error';
        return $this->possiblePayPalLevels[$logLevel] <= $this->possiblePayPalLevels[$level];
    }
}
