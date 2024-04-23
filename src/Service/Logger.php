<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Core\Registry;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Service Logger
 */
class Logger implements LoggerInterface
{
    private LoggerInterface $moduleLogger;

    public function __construct(
        LoggerInterface $moduleLogger
    ) {
        $this->moduleLogger = $moduleLogger;
    }

    private array $possiblePayPalLevels = [
        'error' => 400,
        'info'  => 200,
        'debug' => 100
    ];

    public function doLog(string $level, string $message, array $exception = []): void
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

    public function emergency($message, array $context = array())
    {
        $this->doLog(__FUNCTION__, $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->doLog(__FUNCTION__, $message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->doLog(__FUNCTION__, $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->doLog(__FUNCTION__, $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->doLog(__FUNCTION__, $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->doLog(__FUNCTION__, $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->doLog(__FUNCTION__, $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->doLog(__FUNCTION__, $message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        if(is_string($level) && $this->isLogLevel($level)) {
            $this->moduleLogger->$level($message, $context);
        }
    }
}
