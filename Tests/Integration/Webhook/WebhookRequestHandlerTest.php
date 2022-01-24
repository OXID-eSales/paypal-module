<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventDispatcher;
use OxidSolutionCatalysts\PayPal\Core\Webhook\EventVerifier;
use OxidSolutionCatalysts\PayPal\Core\RequestReader;
use OxidSolutionCatalysts\PayPal\Core\Webhook\RequestHandler as WebhookRequestHandler;

final class WebhookRequestHandlerTest extends UnitTestCase
{
    public function testValidCall(): void
    {
        $requestReader = $this->getMockBuilder(RequestReader::class)
            ->getMock();
        $requestReader->expects($this->any())
            ->method('getRawPost')
            ->willReturn(json_encode(['event_type' => 'CHECKOUT.ORDER.COMPLETED', 'bla' => 'foo']));

        $verificationService = $this->getMockBuilder(EventVerifier::class)
            ->getMock();
        $verificationService->expects($this->any())
            ->method('verify')
            ->willReturn(true);

        $dispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->getMock();
        $dispatcher->expects($this->any())
            ->method('dispatch')
            ->willReturn(true);

        $webhookRequestHandler = new WebhookRequestHandler($requestReader, $verificationService, $dispatcher);

        $this->assertTrue($webhookRequestHandler->process());
    }

    public function testInvalidJsonCall(): void
    {
        $requestReader = $this->getMockBuilder(RequestReader::class)
            ->getMock();
        $requestReader->expects($this->any())
            ->method('getRawPost')
            ->willReturn('this is no json');

        $verificationService = $this->getMockBuilder(EventVerifier::class)
            ->getMock();
        $verificationService->expects($this->any())
            ->method('verify')
            ->willReturn(true);

        $dispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->getMock();
        $dispatcher->expects($this->never())
            ->method('dispatch');

        $loggerMock = $this
            ->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        Registry::set('logger', $loggerMock);

        $webhookRequestHandler = new WebhookRequestHandler($requestReader, $verificationService, $dispatcher);

        $this->assertFalse($webhookRequestHandler->process());
    }

    public function testUnverifiableCall(): void
    {
        $requestReader = $this->getMockBuilder(RequestReader::class)
            ->getMock();
        $requestReader->expects($this->any())
            ->method('getRawPost')
            ->willReturn(json_encode(['event_type' => 'CHECKOUT.ORDER.COMPLETED', 'bla' => 'foo']));
        $requestReader->expects($this->any())
            ->method('getHeaders')
            ->willReturn([]);

        $verificationService = new EventVerifier();

        $dispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->getMock();
        $dispatcher->expects($this->never())
            ->method('dispatch');

        $loggerMock = $this
            ->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        Registry::set('logger', $loggerMock);

        $webhookRequestHandler = new WebhookRequestHandler($requestReader, $verificationService, $dispatcher);

        $this->assertFalse($webhookRequestHandler->process());
    }
}