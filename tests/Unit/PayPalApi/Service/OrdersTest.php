<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Service;

use GuzzleHttp\Psr7\Utils;
use OxidSolutionCatalysts\PayPalApi\Client;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ConfirmOrderRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderAuthorizeRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderCaptureRequest;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Service\Orders;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class OrdersTest extends TestCase
{
    private Client $mockClient;
    private LoggerInterface $mockLogger;
    private Orders $ordersService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->createMock(Client::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        
        $this->mockClient->method('getLogger')->willReturn($this->mockLogger);
        
        $this->ordersService = new Orders($this->mockClient);
    }

    public function testCreateOrderWithDefaultPreference(): void
    {
        $orderRequest = new OrderRequest(['intent' => 'CAPTURE']);
        $partnerAttributionId = 'partner-123';
        $clientMetadataId = 'client-456';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        
        $expectedOrderData = [
            'id' => 'ORDER_ID_123',
            'status' => 'CREATED',
            'intent' => 'CAPTURE'
        ];

        $this->setupMockForSendMethod($mockRequest, $mockResponse);
        
        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'POST',
                '/v2/checkout/orders',
                $this->callback(function ($headers) use ($partnerAttributionId, $clientMetadataId) {
                    return $headers['Content-Type'] === 'application/json'
                        && $headers['PayPal-Partner-Attribution-Id'] === $partnerAttributionId
                        && $headers['PayPal-Client-Metadata-Id'] === $clientMetadataId
                        && $headers['Prefer'] === 'return=minimal'
                        && isset($headers['PayPal-Request-Id']);
                }),
                json_encode($orderRequest)
            )
            ->willReturn($mockRequest);

        $responseBodyStream = Utils::streamFor(json_encode($expectedOrderData));
        $mockResponse->method('getBody')->willReturn($responseBodyStream);

        $result = $this->ordersService->createOrder($orderRequest, $partnerAttributionId, $clientMetadataId);

        $this->assertInstanceOf(Order::class, $result);
    }

    public function testCreateOrderWithRepresentationPreference(): void
    {
        $orderRequest = new OrderRequest(['intent' => 'AUTHORIZE']);
        $partnerAttributionId = 'partner-123';
        $clientMetadataId = 'client-456';
        $prefer = 'return=representation';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->setupMockForSendMethod($mockRequest, $mockResponse);

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'POST',
                '/v2/checkout/orders',
                $this->callback(function ($headers) use ($prefer) {
                    return $headers['Prefer'] === $prefer;
                }),
                $this->anything()
            )
            ->willReturn($mockRequest);

        $responseBodyStream = Utils::streamFor('{"id": "ORDER_ID", "status": "CREATED"}');
        $mockResponse->method('getBody')->willReturn($responseBodyStream);

        $this->ordersService->createOrder($orderRequest, $partnerAttributionId, $clientMetadataId, $prefer);
    }

    public function testShowOrderDetails(): void
    {
        $orderId = 'ORDER_123';
        $fields = 'payment_source';
        $partnerAttributionId = 'partner-123';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->setupMockForSendMethod($mockRequest, $mockResponse);

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'GET',
                "/v2/checkout/orders/{$orderId}?fields={$fields}",
                $this->callback(function ($headers) use ($partnerAttributionId) {
                    return $headers['Content-Type'] === 'application/json'
                        && $headers['PayPal-Partner-Attribution-Id'] === $partnerAttributionId;
                }),
                null
            )
            ->willReturn($mockRequest);

        $orderData = ['id' => $orderId, 'status' => 'APPROVED'];
        $responseBodyStream = Utils::streamFor(json_encode($orderData));
        $mockResponse->method('getBody')->willReturn($responseBodyStream);

        $result = $this->ordersService->showOrderDetails($orderId, $fields, $partnerAttributionId);

        $this->assertInstanceOf(Order::class, $result);
    }

    public function testUpdateOrderWithPartnerAttributionId(): void
    {
        $orderId = 'ORDER_123';
        $patchRequest = [['op' => 'replace', 'path' => '/intent', 'value' => 'CAPTURE']];
        $partnerAttributionId = 'partner-123';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->setupMockForSendMethod($mockRequest, $mockResponse);

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'PATCH',
                "/v2/checkout/orders/{$orderId}",
                $this->callback(function ($headers) use ($partnerAttributionId) {
                    return $headers['Content-Type'] === 'application/json'
                        && $headers['PayPal-Partner-Attribution-Id'] === $partnerAttributionId;
                }),
                json_encode($patchRequest)
            )
            ->willReturn($mockRequest);

        $this->ordersService->updateOrder($orderId, $patchRequest, $partnerAttributionId);
    }

    public function testUpdateOrderWithoutPartnerAttributionId(): void
    {
        $orderId = 'ORDER_123';
        $patchRequest = [['op' => 'replace', 'path' => '/intent', 'value' => 'CAPTURE']];

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->setupMockForSendMethod($mockRequest, $mockResponse);

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'PATCH',
                "/v2/checkout/orders/{$orderId}",
                $this->callback(function ($headers) {
                    return $headers['Content-Type'] === 'application/json'
                        && !isset($headers['PayPal-Partner-Attribution-Id']);
                }),
                json_encode($patchRequest)
            )
            ->willReturn($mockRequest);

        $this->ordersService->updateOrder($orderId, $patchRequest);
    }

    public function testConfirmTheOrderWithPartnerAttributionId(): void
    {
        $orderId = 'ORDER_123';
        $clientMetadataId = 'client-456';
        $confirmRequest = new ConfirmOrderRequest(['payment_source' => ['paypal' => []]]);
        $partnerAttributionId = 'partner-123';
        $prefer = 'return=representation';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->setupMockForSendMethod($mockRequest, $mockResponse);

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'POST',
                "/v2/checkout/orders/{$orderId}/confirm-payment-source",
                $this->callback(function ($headers) use ($clientMetadataId, $partnerAttributionId, $prefer) {
                    return $headers['PayPal-Client-Metadata-Id'] === $clientMetadataId
                        && $headers['Content-Type'] === 'application/json'
                        && $headers['Prefer'] === $prefer
                        && $headers['PayPal-Partner-Attribution-Id'] === $partnerAttributionId;
                }),
                json_encode($confirmRequest)
            )
            ->willReturn($mockRequest);

        $orderData = ['id' => $orderId, 'status' => 'APPROVED'];
        $responseBodyStream = Utils::streamFor(json_encode($orderData));
        $mockResponse->method('getBody')->willReturn($responseBodyStream);

        $result = $this->ordersService->confirmTheOrder($clientMetadataId, $orderId, $confirmRequest, $partnerAttributionId, $prefer);

        $this->assertInstanceOf(Order::class, $result);
    }

    public function testConfirmTheOrderWithoutPartnerAttributionId(): void
    {
        $orderId = 'ORDER_123';
        $clientMetadataId = 'client-456';
        $confirmRequest = new ConfirmOrderRequest(['payment_source' => ['paypal' => []]]);

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->setupMockForSendMethod($mockRequest, $mockResponse);

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'POST',
                "/v2/checkout/orders/{$orderId}/confirm-payment-source",
                $this->callback(function ($headers) {
                    return !isset($headers['PayPal-Partner-Attribution-Id'])
                        && $headers['Prefer'] === 'return=minimal'; // default value
                }),
                $this->anything()
            )
            ->willReturn($mockRequest);

        $responseBodyStream = Utils::streamFor('{"id": "ORDER_123", "status": "APPROVED"}');
        $mockResponse->method('getBody')->willReturn($responseBodyStream);

        $this->ordersService->confirmTheOrder($clientMetadataId, $orderId, $confirmRequest);
    }

    public function testAuthorizePaymentForOrderWithPartnerAttributionId(): void
    {
        $orderId = 'ORDER_123';
        $clientMetadataId = 'client-456';
        $authorizeRequest = new OrderAuthorizeRequest(['payment_source' => ['paypal' => []]]);
        $authAssertion = 'jwt-token';
        $partnerAttributionId = 'partner-123';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->setupMockForSendMethod($mockRequest, $mockResponse);

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'POST',
                "/v2/checkout/orders/{$orderId}/authorize",
                $this->callback(function ($headers) use ($clientMetadataId, $partnerAttributionId) {
                    return $headers['PayPal-Client-Metadata-Id'] === $clientMetadataId
                        && $headers['Content-Type'] === 'application/json'
                        && $headers['Prefer'] === 'return=minimal'
                        && $headers['PayPal-Partner-Attribution-Id'] === $partnerAttributionId
                        && !isset($headers['PayPal-Auth-Assertion']); // deprecated
                }),
                json_encode($authorizeRequest)
            )
            ->willReturn($mockRequest);

        $orderData = ['id' => $orderId, 'status' => 'COMPLETED'];
        $responseBodyStream = Utils::streamFor(json_encode($orderData));
        $mockResponse->method('getBody')->willReturn($responseBodyStream);

        $result = $this->ordersService->authorizePaymentForOrder($clientMetadataId, $orderId, $authorizeRequest, $authAssertion, $partnerAttributionId);

        $this->assertInstanceOf(Order::class, $result);
    }

    public function testCapturePaymentForOrderWithoutPartnerAttributionId(): void
    {
        $orderId = 'ORDER_123';
        $clientMetadataId = 'client-456';
        $captureRequest = new OrderCaptureRequest(['payment_source' => ['paypal' => []]]);
        $authAssertion = 'jwt-token';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->setupMockForSendMethod($mockRequest, $mockResponse);

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'POST',
                "/v2/checkout/orders/{$orderId}/capture",
                $this->callback(function ($headers) {
                    return !isset($headers['PayPal-Partner-Attribution-Id'])
                        && !isset($headers['PayPal-Auth-Assertion']); // deprecated
                }),
                $this->anything()
            )
            ->willReturn($mockRequest);

        $orderData = ['id' => $orderId, 'status' => 'COMPLETED'];
        $responseBodyStream = Utils::streamFor(json_encode($orderData));
        $mockResponse->method('getBody')->willReturn($responseBodyStream);

        $result = $this->ordersService->capturePaymentForOrder($clientMetadataId, $orderId, $captureRequest, $authAssertion);

        $this->assertInstanceOf(Order::class, $result);
    }

    public function testSaveOrder(): void
    {
        $orderId = 'ORDER_123';
        $clientMetadataId = 'client-456';
        $partnerAttributionId = 'partner-123';
        $prefer = 'return=representation';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->setupMockForSendMethod($mockRequest, $mockResponse);

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'POST',
                "/v2/checkout/orders/{$orderId}/save",
                $this->callback(function ($headers) use ($clientMetadataId, $partnerAttributionId, $prefer) {
                    return $headers['PayPal-Client-Metadata-Id'] === $clientMetadataId
                        && $headers['Prefer'] === $prefer
                        && $headers['PayPal-Partner-Attribution-Id'] === $partnerAttributionId;
                }),
                null
            )
            ->willReturn($mockRequest);

        $orderData = ['id' => $orderId, 'status' => 'SAVED'];
        $responseBodyStream = Utils::streamFor(json_encode($orderData));
        $mockResponse->method('getBody')->willReturn($responseBodyStream);

        $result = $this->ordersService->saveOrder($clientMetadataId, $orderId, $partnerAttributionId, $prefer);

        $this->assertInstanceOf(Order::class, $result);
    }

    public function testVoidOrder(): void
    {
        $orderId = 'ORDER_123';
        $clientMetadataId = 'client-456';
        $prefer = 'return=minimal';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->setupMockForSendMethod($mockRequest, $mockResponse);

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'POST',
                "/v2/checkout/orders/{$orderId}/void",
                $this->callback(function ($headers) use ($clientMetadataId, $prefer) {
                    return $headers['PayPal-Client-Metadata-Id'] === $clientMetadataId
                        && $headers['Prefer'] === $prefer;
                }),
                null
            )
            ->willReturn($mockRequest);

        // Void returns no content, just verify method doesn't throw
        $this->ordersService->voidOrder($clientMetadataId, $orderId, $prefer);
        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('preferenceProvider')]
    public function testPreferenceHeaderValues(string $preference): void
    {
        $orderRequest = new OrderRequest(['intent' => 'CAPTURE']);
        $partnerAttributionId = 'partner-123';
        $clientMetadataId = 'client-456';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->setupMockForSendMethod($mockRequest, $mockResponse);

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'POST',
                '/v2/checkout/orders',
                $this->callback(function ($headers) use ($preference) {
                    return $headers['Prefer'] === $preference;
                }),
                $this->anything()
            )
            ->willReturn($mockRequest);

        $responseBodyStream = Utils::streamFor('{"id": "ORDER_ID", "status": "CREATED"}');
        $mockResponse->method('getBody')->willReturn($responseBodyStream);

        $this->ordersService->createOrder($orderRequest, $partnerAttributionId, $clientMetadataId, $preference);
    }

    public static function preferenceProvider(): array
    {
        return [
            ['return=minimal'],
            ['return=representation'],
        ];
    }

    private function setupMockForSendMethod(RequestInterface $mockRequest, ResponseInterface $mockResponse): void
    {
        $this->mockClient->method('getActionHash')->willReturn('test-hash');
        $this->mockClient->method('send')->willReturn($mockResponse);
        
        $requestBodyStream = Utils::streamFor('{}');
        $mockRequest->method('getBody')->willReturn($requestBodyStream);
        $mockRequest->method('getHeaders')->willReturn([]);
        
        $this->mockLogger->method('log');
    }
}