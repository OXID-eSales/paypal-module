<?php

/**
 * This file is part of OXID eSales PayPal module.
 *
 * OXID eSales PayPal module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eSales PayPal module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales PayPal module.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2020
 */

namespace OxidProfessionalServices\PayPal\Core\Webhook\Handler;

use OxidEsales\Eshop\Application\Model\Order as OxOrder;
use OxidEsales\Eshop\Core\Registry;
use OxidProfessionalServices\PayPal\Api\Exception\ApiException;
use OxidProfessionalServices\PayPal\Api\Model\Orders\Capture;
use OxidProfessionalServices\PayPal\Api\Model\Orders\Order;
use OxidProfessionalServices\PayPal\Api\Model\Orders\Order as OrderResponse;
use OxidProfessionalServices\PayPal\Api\Model\Orders\OrderCaptureRequest;
use OxidProfessionalServices\PayPal\Core\ServiceFactory;
use OxidProfessionalServices\PayPal\Core\Webhook\Event;
use OxidProfessionalServices\PayPal\Core\Webhook\Exception\EventException;

class CheckoutOrderCompletedHandler implements HandlerInterface
{
    /**
     * @inheritDoc
     * @throws ApiException
     */
    public function handle(Event $event): void
    {
        $data = $event->getData()['resource'];

        $payPalOrderId = $data['id'] ?? '';
        $oxidOrderId = $data->purchase_units[0]->custom_id ?? '';

        if (!$oxidOrderId || !$payPalOrderId) {
            throw new EventException('Required data not found in request');
        }

        $order = oxNew(OxOrder::class);
        if (!$order->load($oxidOrderId)) {
            throw new EventException(sprintf('Oxid order %s not found', $oxidOrderId));
        }

        $response = $this->capturePayment($payPalOrderId);

        if (
            $response->status == OrderResponse::STATUS_COMPLETED &&
            $response->purchase_units[0]->payments->captures[0]->status == Capture::STATUS_COMPLETED
        ) {
            $order->markOrderPaid();
        }
    }

    /**
     * Captures payment for given order
     *
     * @param string $orderId
     *
     * @return Order
     * @throws ApiException
     */
    private function capturePayment(string $orderId): Order
    {
        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = Registry::get(ServiceFactory::class);
        $service = $serviceFactory->getOrderService();
        $request = new OrderCaptureRequest();

        return $service->capturePaymentForOrder('', $orderId, $request, '');
    }
}
