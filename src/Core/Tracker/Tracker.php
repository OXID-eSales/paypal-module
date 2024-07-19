<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Tracker;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Service\GenericService;

class Tracker
{
    use ServiceContainer;

    public const STATUS_SHIPPED = 'SHIPPED';
    public const STATUS_ON_HOLD = 'ON_HOLD';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $possibleStatus = [
        self::STATUS_CANCELLED, self::STATUS_ON_HOLD,
        self::STATUS_DELIVERED, self::STATUS_CANCELLED
    ];

    protected $defaultStatus = self::STATUS_SHIPPED;
    public function sendtracking(
        string $transactionId,
        string $trackingNumber,
        string $carrier,
        string $status = self::STATUS_SHIPPED
    ): bool {
        $result = false;
        $status = in_array($status, $this->possibleStatus, true) ? $status : $this->defaultStatus;

        try {
            $paypload = [
                'trackers' => [[
                    'transaction_id' => $transactionId,
                    'tracking_number' => $trackingNumber,
                    'status' => $status,
                    'carrier' => $carrier
                ]]
            ];

            /** @var GenericService $notificationService */
            $trackerService = Registry::get(ServiceFactory::class)->getTrackerService();
            $trackerResponse = $trackerService->request('POST', $paypload);

            $result = $trackerResponse['tracker_identifiers'][0]['tracking_number'] === $trackingNumber;
        } catch (\Exception $exception) {
            /** @var Logger $logger */
            $logger = $this->getServiceFromContainer(Logger::class);
            $logger->log(
                'error',
                'PayPal sending Tracker failed: ' . $exception->getMessage(),
                [$exception]
            );
        }

        return $result;
    }
}
