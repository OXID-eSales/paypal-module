<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Service;

/**
 * Interface for PayPal logging services
 */
interface LoggerInterface
{
    /**
     * Log a message with the specified level
     *
     * @param string $level The log level (error, info, debug)
     * @param string $message The message to log
     * @param array $exception Optional exception context data
     * @return void
     */
    public function log(string $level, string $message, array $exception = []): void;

    /**
     * Check if a log level should be logged based on configuration
     *
     * @param string $level The log level to check
     * @return bool True if the level should be logged, false otherwise
     */
    public function isLogLevel(string $level): bool;
}