<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Onboarding;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Core\Onboarding\Webhook;
use OxidSolutionCatalysts\PayPal\Exception\OnboardingException;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;

final class WebhookTest extends BaseTestCase
{
    protected const TEST_WEBHOOK_URL = 'https://localhost.local?cl=oscpaypalwebhook';

    public function testGetWebhookEndpoint(): void
    {
        $service = oxNew(Webhook::class);

        $this->doAssertStringContainsString('oscpaypalwebhook', $service->getWebhookEndpoint());
        $this->doAssertStringContainsString(Registry::getConfig()->getShopUrl(), $service->getWebhookEndpoint());
    }

    public function testGetAvailableEvents(): void
    {
        $service = oxNew(Webhook::class);

        $this->doAssertStringContainsString('CHECKOUT.ORDER.COMPLETED', serialize($service->getAvailableEventNames()));
    }

    public function testNonSslEndpoint(): void
    {
        $service = $this->getServiceMock('http://localhost');

        $this->expectException(OnboardingException::class);
        $this->expectExceptionMessage(OnboardingException::nonsslUrl()->getMessage());

        $service->ensureWebhook();
    }

    public function testWebhookCreationRoundtrip(): void
    {
        $this->markTestSkipped('Test removes existing webhooks, only use manually until refactored');

        $this->ensureCleanUp();

        $loggerMock = $this->getPsrLoggerMock();
        $loggerMock->expects($this->never())
            ->method('error');
        Registry::set('logger', $loggerMock);

        $service = $this->getServiceMock();

        //we start from clean slate for this url
        $hook = $service->getHookForUrl(self::TEST_WEBHOOK_URL);
        $this->assertEmpty($hook);

        //ensure webhook is saved
        $webhookId = $service->ensureWebhook();
        $this->assertNotEmpty($webhookId);

        $hook = $service->getHookForUrl(self::TEST_WEBHOOK_URL);
        $this->assertNotEmpty($hook);

        $this->assertEmpty(array_diff($service->getEnabledEvents($hook), $service->getAvailableEventNames()));

        $this->ensureCleanUp();
    }

    public function testWebhookCreationNewEvents(): void
    {
        $this->markTestSkipped('Test removes existing webhooks, only use manually until refactored');

        $this->ensureCleanUp();

        $loggerMock = $this->getPsrLoggerMock();
        $loggerMock->expects($this->never())
            ->method('error');
        Registry::set('logger', $loggerMock);

        //create webhook with subset of events
        $service = $this->getServiceMock(self::TEST_WEBHOOK_URL, ['getAvailableEventNames']);
        $service->expects($this->any())
            ->method('getAvailableEventNames')
            ->willReturn([['name' => 'PAYMENT.SALE.COMPLETED']]);

        //we start from clean slate for this url
        $hook = $service->getHookForUrl(self::TEST_WEBHOOK_URL);
        $this->assertEmpty($hook);

        //ensure webhook is saved
        $webhookId = $service->ensureWebhook();
        $this->assertNotEmpty($webhookId);
        $hook = $service->getHookForUrl(self::TEST_WEBHOOK_URL);
        $this->assertSame([['name' => 'PAYMENT.SALE.COMPLETED']], $service->getEnabledEvents($hook));

        //simulate new available webhook event
        $service = $this->getServiceMock();
        $newWebhookId = $service->ensureWebhook();

        $hook = $service->getHookForUrl(self::TEST_WEBHOOK_URL);
        $this->assertEmpty(array_diff($service->getEnabledEvents($hook), $service->getAvailableEventNames()));

        $this->ensureCleanUp();
    }

    protected function getServiceMock(string $url = self::TEST_WEBHOOK_URL, array $addMockMethods = []): Webhook
    {
        $service = $this->getMockBuilder(Webhook::class)
            ->setMethods(array_merge(['getWebhookEndpoint'], $addMockMethods))
            ->getMock();
        $service->expects($this->any())
            ->method('getWebhookEndpoint')
            ->willReturn($url);

        return $service;
    }

    protected function ensureCleanUp()
    {
        $service = $this->getServiceMock();

        $hook = $service->getHookForUrl(self::TEST_WEBHOOK_URL);
        $id = (isset($hook['id'])) ? $hook['id'] : '';
        $service->removeWebhook($id);

        $hook = $service->getHookForUrl(self::TEST_WEBHOOK_URL);
        $this->assertEquals([], $hook);
    }
}
