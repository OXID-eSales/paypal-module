<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Api\VaultingService;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Event as WebhookEvent;
use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\VaultPaymentTokenCreatedHandler;
use OxidSolutionCatalysts\PayPal\Exception\WebhookEventException;
use OxidSolutionCatalysts\PayPal\Model\User;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use PHPUnit\Framework\MockObject\MockObject;

final class VaultPaymentTokenCreatedHandlerTest extends WebhookHandlerBaseTestCase
{
    public const WEBHOOK_EVENT = 'VAULT.PAYMENT-TOKEN.CREATED';

    public function testRequestMissingData(): void
    {
        $event = new WebhookEvent([], self::WEBHOOK_EVENT);

        $this->expectException(WebhookEventException::class);
        $this->expectExceptionMessage(WebhookEventException::mandatoryDataNotFound()->getMessage());

        $handler = oxNew(VaultPaymentTokenCreatedHandler::class);
        $handler->handle($event);
    }

    public function testMissingCustomerId(): void
    {
        $data = $this->getRequestData('vault_payment_token_created.json');
        unset($data['resource']['customer']['id']);

        $event = new WebhookEvent($data, self::WEBHOOK_EVENT);

        $loggerMock = $this->getPsrLoggerMock();
        /** @var MockObject $loggerMock */
        $loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'debug',
                'VAULT.PAYMENT-TOKEN.CREATED webhook received without customer.id field.',
                $this->callback(function ($context) {
                    return isset($context['event_payload_keys']);
                })
            );

        $handler = $this->getMockBuilder(VaultPaymentTokenCreatedHandler::class)
            ->onlyMethods(['getLogger'])
            ->getMock();

        $handler->method('getLogger')->willReturn($loggerMock);
        $handler->handle($event);
    }

    public function testUserNotFound(): void
    {
        $data = $this->getRequestData('vault_payment_token_created.json');
        $event = new WebhookEvent($data, self::WEBHOOK_EVENT);

        $loggerMock = $this->getPsrLoggerMock();
        /** @var MockObject $loggerMock */
        $loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'debug',
                'VAULT.PAYMENT-TOKEN.CREATED webhook error: shop user unknown',
                []
            );

        $handler = $this->getMockBuilder(VaultPaymentTokenCreatedHandler::class)
            ->onlyMethods(['getLogger'])
            ->getMock();

        $handler->method('getLogger')->willReturn($loggerMock);
        $handler->handle($event);
    }

    public function testSuccessfulVaultTokenCreation(): void
    {
        $data = $this->getRequestData('vault_payment_token_created.json');
        $payPalOrderId = $data['resource']['metadata']['order_id'];
        $customerId = $data['resource']['customer']['id'];
        $event = new WebhookEvent($data, self::WEBHOOK_EVENT);

        $this->prepareTestData($payPalOrderId);

        $userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userMock->expects($this->any())
            ->method('getId')
            ->willReturn('_test_user_id');

        $orderMock = $this->getMockBuilder(EshopModelOrder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->any())
            ->method('getOrderUser')
            ->willReturn($userMock);

        $paymentServiceMock = $this->getMockBuilder(PaymentService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentServiceMock->expects($this->once())
            ->method('saveCustomerIdToUser')
            ->with($customerId, $userMock);

        $vaultingServiceMock = $this->getMockBuilder(VaultingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $vaultingServiceMock->expects($this->once())
            ->method('clearVaultedTokenCache');
        $vaultingServiceMock->expects($this->once())
            ->method('setTrackingId');

        $serviceFactoryMock = $this->getMockBuilder(ServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $serviceFactoryMock->expects($this->any())
            ->method('getVaultingService')
            ->willReturn($vaultingServiceMock);
        $serviceFactoryMock->expects($this->any())
            ->method('getOrderService')
            ->willReturn($this->createOrderServiceMock());

        Registry::set(ServiceFactory::class, $serviceFactoryMock);

        $handler = $this->getMockBuilder(VaultPaymentTokenCreatedHandler::class)
            ->onlyMethods(['getOrderByPayPalOrderId', 'getPaymentService'])
            ->getMock();
        $handler->expects($this->once())
            ->method('getOrderByPayPalOrderId')
            ->with($payPalOrderId)
            ->willReturn($orderMock);
        $handler->expects($this->once())
            ->method('getPaymentService')
            ->willReturn($paymentServiceMock);

        $handler->handle($event);
    }

    private function createOrderServiceMock()
    {
        $orderServiceMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['showOrderDetails'])
            ->getMock();

        $orderDetailsMock = new \stdClass();
        $orderDetailsMock->purchase_units = [
            (object)[
                'custom_id' => json_encode(['id' => 'test_trace_id'])
            ]
        ];

        $orderServiceMock->expects($this->any())
            ->method('showOrderDetails')
            ->willReturn($orderDetailsMock);

        return $orderServiceMock;
    }
}
