<?php

namespace OxidSolutionCatalysts\PayPal\Service\GooglePay;

use Exception;
use OxidEsales\Eshop\Application\Model\Order;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use Psr\Log\LoggerInterface;

class GooglePayPayPalService
{
    private LoggerInterface $logger;
    private ModuleSettings $moduleSettings;

    public function __construct(LoggerInterface $logger, ModuleSettings $moduleSettings)
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
