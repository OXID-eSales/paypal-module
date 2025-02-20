<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service\GooglePay;

use Exception;
use OxidSolutionCatalysts\PayPal\Model\Order;
use OxidSolutionCatalysts\PayPal\Service\Logger;

class GooglePayPayPalService
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
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
            $this->logger->log(
                'error',
                __CLASS__ . ': failure during finalizeOrderAfterExternalPayment',
                [$exception]
            );
        }

        return false;
    }
}
