<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use Psr\Log\LoggerInterface;

/**
 * Class Config
 */
class Logger
{
    use ServiceContainer;

    private array $possiblePayPalLevels = [
        'error' => 400,
        'info'  => 200,
        'debug' => 100
    ];

    public function log(string $level, string $message, array $exception = []): void
    {
        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');

        if ($this->isLogLevel($level)) {
            $logger->{$level}($message, $exception);
        }
    }

    public function isLogLevel(string $level): bool
    {

        $logLevel = Registry::getConfig()->getConfigParam('sLogLevel') ?? 'error';
        $logLevel = isset($possiblePayPalLevels[$logLevel]) ? $logLevel : 'error';
        $level = isset($possiblePayPalLevels[$level]) ? $level : 'error';
        return $this->possiblePayPalLevels[$logLevel] <= $this->possiblePayPalLevels[$level];
    }
}
