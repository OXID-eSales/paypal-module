<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service\GooglePay;

use Exception;
use OxidSolutionCatalysts\PayPal\Model\Order;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;

class GooglePayPayPalService
{
    private Logger $logger;
    private ModuleSettings $moduleSettings;

    public function __construct(Logger $logger, ModuleSettings $moduleSettings)
    {
        $this->logger = $logger;
        $this->moduleSettings = $moduleSettings;
    }

    public function finalizeGooglePay(string $oxidOrderId, string $payPalOrderId, bool $forceFetchDetails): bool
    {
        try {
            /** @var Order $order */
            $order = oxNew(Order::class);
            $order->load($oxidOrderId);
            $order->finalizeOrderAfterExternalPayment($payPalOrderId, $forceFetchDetails);
            return true;
        } catch (Exception $exception) {
            if ($this->moduleSettings->getPayPalDebugLevel() === 'debug' || $this->moduleSettings->getPayPalDebugLevel() === 'error') {
                $this->logger->log(
                    'error',
                    __CLASS__ . ': failure during finalizeOrderAfterExternalPayment',
                    [$exception]
                );
            }
        }

        return false;
    }
}
