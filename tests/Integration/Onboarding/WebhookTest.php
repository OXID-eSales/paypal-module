<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Onboarding;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Onboarding\Webhook;
use OxidSolutionCatalysts\PayPal\Exception\OnboardingException;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;

final class WebhookTest extends BaseTestCase
{
    protected const TEST_WEBHOOK_URL = 'https://localhost.local?cl=oscpaypalwebhook';

    public function testGetWebhookEndpoint(): void
    {
        $service = oxNew(Webhook::class);

        $this->assertStringContainsString('oscpaypalwebhook', $service->getWebhookEndpoint());
        $this->assertStringContainsString(Registry::getConfig()->getShopUrl(), $service->getWebhookEndpoint());
    }

    public function testGetAvailableEvents(): void
    {
        $service = oxNew(Webhook::class);

        $this->assertStringContainsString(
            'CHECKOUT.ORDER.COMPLETED',
            serialize($service->getAvailableEventNames())
        );
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
        $this->ensureCleanUp();

        $loggerMock = $this->getPsrLoggerMock();
        $loggerMock->expects($this->never())
            ->method('error');
        Registry::set('logger', $loggerMock);

        $service = $this->getServiceMock();

        //we start from clean slate for this url
        $hook = $service->getHookForUrl(self::TEST_WEBHOOK_URL);
        $this->assertIsArray($hook);

        //ensure webhook is saved
        $webhookId = $service->ensureWebhook();
        $this->assertIsString($webhookId);

        $hook = $service->getHookForUrl(self::TEST_WEBHOOK_URL);
        $this->assertIsArray($hook);

        $this->assertEmpty(array_diff($service->getEnabledEvents($hook), $service->getAvailableEventNames()));

        if (empty($hook['id'])) {
            $this->fail('Webhook ID should not be empty after creation');
        }

        $service->removeWebhook($hook['id']);

        $hook = $service->getHookForUrl(self::TEST_WEBHOOK_URL);
        $this->assertEquals([], $hook);
    }

    protected function getServiceMock(string $url = self::TEST_WEBHOOK_URL, array $addMockMethods = []): Webhook
    {
        $service = $this->getMockBuilder(Webhook::class)
            ->onlyMethods(array_merge(['getWebhookEndpoint'], $addMockMethods))
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
    }
}
