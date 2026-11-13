<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Service;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\PatchRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Service\Factory\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\OrderProcessTrackingService;
use OxidSolutionCatalysts\PayPal\Service\OrderRepository;
use OxidSolutionCatalysts\PayPal\Service\Payment as PaymentService;
use OxidSolutionCatalysts\PayPal\Service\SCAValidatorInterface;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Service\Orders;
use Psr\Log\LoggerInterface;

/**
 * When PayPal refuses to create the order, the reason is logged - but only the
 * classified error, never the request with its authorization header.
 */
final class PaymentCreateOrderRefusedTest extends BaseTestCase
{
    public function testRefusedOrderCreationLogsClassifiedReasonOnly(): void
    {
        $refusal = new ApiException(new RequestException(
            'Bad Request',
            new Request(
                'POST',
                'https://api.sandbox.paypal.com/v2/checkout/orders',
                ['Authorization' => 'Bearer secret-token'],
                '{"payment_source":{"google_pay":{"name":""}}}'
            ),
            new Response(400, [], json_encode([
                'name' => 'UNPROCESSABLE_ENTITY',
                'message' => 'The requested action could not be performed.',
                'details' => [[
                    'field' => '/payment_source/google_pay/name',
                    'issue' => 'INVALID_STRING_LENGTH',
                    'description' => 'The value of a field is either too short or too long.',
                ]],
            ]))
        ));

        $orderService = $this->createMock(Orders::class);
        $orderService->method('createOrder')->willThrowException($refusal);
        $serviceFactory = $this->createMock(ServiceFactory::class);
        $serviceFactory->method('getOrderService')->willReturn($orderService);

        $orderRequestFactory = $this->createMock(OrderRequestFactory::class);
        $orderRequestFactory->method('getRequest')->willReturn(new OrderRequest());

        $loggedContexts = [];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log')->willReturnCallback(
            function ($level, $message, array $context = []) use (&$loggedContexts): void {
                $loggedContexts[] = [$level, (string)$message, $context];
            }
        );

        $paymentService = new PaymentService(
            Registry::getSession(),
            $this->createMock(OrderRepository::class),
            $this->createMock(SCAValidatorInterface::class),
            $this->createMock(ModuleSettings::class),
            $logger,
            $this->createMock(OrderProcessTrackingService::class),
            $serviceFactory,
            Registry::get(PatchRequestFactory::class),
            $orderRequestFactory
        );

        $response = $paymentService->doCreatePayPalOrder(
            oxNew(EshopModelBasket::class),
            OrderRequest::INTENT_CAPTURE
        );

        $this->assertNull($response);

        $refusalLogs = array_values(array_filter(
            $loggedContexts,
            static fn (array $entry): bool => $entry[1] === 'PayPal refused to create the order.'
        ));
        $this->assertCount(1, $refusalLogs);
        [$level, , $context] = $refusalLogs[0];
        $this->assertSame('warning', $level);
        $this->assertSame(400, $context['httpCode']);
        $this->assertSame('INVALID_STRING_LENGTH', $context['issue']);
        $this->assertSame('The value of a field is either too short or too long.', $context['description']);

        // Neither the request nor the authorization header may end up in the log.
        $this->assertStringNotContainsString('secret-token', json_encode($loggedContexts));
        $this->assertStringNotContainsString('google_pay', json_encode($loggedContexts));
    }
}
