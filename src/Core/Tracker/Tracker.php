<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Tracker;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Service\GenericService;
use Psr\Log\LoggerInterface;

class Tracker
{
    use ServiceContainer;

    public const STATUS_SHIPPED = 'SHIPPED';
    public const STATUS_ON_HOLD = 'ON_HOLD';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $possibleStatus = [
        self::STATUS_SHIPPED,
        self::STATUS_ON_HOLD,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    protected $defaultStatus = self::STATUS_SHIPPED;

    /**
     * Send tracking information to PayPal for a given order. Uses the v2 Orders
     * tracking endpoint (POST /v2/checkout/orders/{id}/track) which works with
     * the standard PPCP capabilities; the previous /v1/shipping/trackers-batch
     * endpoint required a separate "Shipping Tracking" app feature that PayPal
     * does not include in the default partner-onboarding set. (0007945)
     *
     * If the same (capture_id, tracking_number) pair was already submitted
     * earlier, PayPal returns 422 "tracker already exists". We fall back to a
     * PATCH on /v2/checkout/orders/{id}/trackers/{tracker_id} so the merchant
     * can correct the carrier or status without creating a duplicate.
     */
    public function sendtracking(
        string $payPalOrderId,
        string $captureId,
        string $trackingNumber,
        string $carrier,
        string $status = self::STATUS_SHIPPED,
        bool $notifyPayer = true
    ): bool {
        $status = in_array($status, $this->possibleStatus, true) ? $status : $this->defaultStatus;

        $payload = [
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
            'capture_id' => $captureId,
            'status' => $status,
            'notify_payer' => $notifyPayer,
        ];

        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');

        $logger->log('info', sprintf(
            'PayPal tracker POST attempt: order=%s capture=%s tracking=%s carrier=%s',
            $payPalOrderId,
            $captureId,
            $trackingNumber,
            $carrier
        ));

        try {
            /** @var GenericService $trackerService */
            $trackerService = Registry::get(ServiceFactory::class)->getTrackerService($payPalOrderId);
            $trackerService->request('POST', $payload);
            $logger->log('info', sprintf(
                'PayPal tracker POST succeeded: order=%s tracker_id=%s',
                $payPalOrderId,
                $captureId . '-' . $trackingNumber
            ));
            return true;
        } catch (ApiException $exception) {
            if ($this->isTrackerAlreadyExistsError($exception)) {
                return $this->patchTracker(
                    $payPalOrderId,
                    $captureId . '-' . $trackingNumber,
                    $carrier,
                    $status
                );
            }
            $logger->log(
                'warning',
                'PayPal sending Tracker failed: ' . $exception->getMessage(),
                [$exception]
            );
        } catch (\Exception $exception) {
            $logger->log(
                'warning',
                'PayPal sending Tracker failed: ' . $exception->getMessage(),
                [$exception]
            );
        }

        return false;
    }

    /**
     * Detect "TRACKER_ALREADY_EXISTS"-style responses (HTTP 422 from PayPal v2)
     * so the caller can fall back to PATCH instead of failing.
     */
    private function isTrackerAlreadyExistsError(ApiException $exception): bool
    {
        if ($exception->getCode() !== 422) {
            return false;
        }
        $message = $exception->getMessage();
        return stripos($message, 'already') !== false
            || stripos($message, 'duplicate') !== false
            || stripos($message, 'TRACKER_ALREADY') !== false;
    }

    /**
     * PATCH an existing tracker to update carrier/status. notify_payer is left
     * out of the patch body on purpose — re-notifying the customer on every
     * carrier correction would spam them; the merchant gets the customer-side
     * notification on the initial POST and not on subsequent updates.
     */
    private function patchTracker(
        string $payPalOrderId,
        string $trackerId,
        string $carrier,
        string $status
    ): bool {
        /** @var LoggerInterface $logger */
        $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');

        try {
            $patchBody = [
                ['op' => 'replace', 'path' => '/carrier', 'value' => $carrier],
                ['op' => 'replace', 'path' => '/status', 'value' => $status],
            ];
            /** @var GenericService $patchService */
            $patchService = Registry::get(ServiceFactory::class)
                ->getTrackerUpdateService($payPalOrderId, $trackerId);
            $patchService->request('PATCH', $patchBody);

            $logger->log('info', sprintf(
                'PayPal tracker PATCH succeeded for order %s tracker %s',
                $payPalOrderId,
                $trackerId
            ));
            return true;
        } catch (\Exception $exception) {
            $logger->log(
                'warning',
                'PayPal updating Tracker failed: ' . $exception->getMessage(),
                [$exception]
            );
        }

        return false;
    }
}
