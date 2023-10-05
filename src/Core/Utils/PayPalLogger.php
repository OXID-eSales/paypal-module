<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Utils;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OxidEsales\Eshop\Core\Registry;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Webmozart\PathUtil\Path;

class PayPalLogger extends AbstractLogger
{
    /**
     * @throws Exception
     */
    private function getLogger(int $loglevel): LoggerInterface
    {
        $logger = new Logger('PayPal Checkout Logger');
        $logger->pushHandler(
            new StreamHandler(
                $this->getPayPalLogFilePath(),
                $loglevel
            )
        );

        return $logger;
    }

    public function log($level, $message, array $context = [])
    {
        $levelName = Logger::getLevels()[strtoupper($level)];
        $this->getLogger($levelName)->addRecord($levelName, $message, $context);
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function getPayPalLogFilePath(): string
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
