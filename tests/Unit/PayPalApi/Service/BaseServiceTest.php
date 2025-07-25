<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Service;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use OxidSolutionCatalysts\PayPalApi\Client;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Service\BaseService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class BaseServiceTest extends TestCase
{
    private Client $mockClient;
    private LoggerInterface $mockLogger;
    private BaseService $baseService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->createMock(Client::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        
        $this->mockClient->method('getLogger')->willReturn($this->mockLogger);
        
        $this->baseService = new BaseService($this->mockClient);
    }

    public function testConstructorSetsClient(): void
    {
        $this->assertSame($this->mockClient, $this->baseService->client);
    }

    public function testSendWithoutParameters(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'GET',
                '/test',
                $this->callback(function ($headers) {
                    return isset($headers['PayPal-Request-Id']);
                }),
                null
            )
            ->willReturn($mockRequest);

        $this->mockClient->expects($this->once())
            ->method('getActionHash')
            ->willReturn('test-hash');

        $requestBodyStream = Utils::streamFor('request-body');
        $mockRequest->method('getBody')->willReturn($requestBodyStream);
        $mockRequest->method('getHeaders')->willReturn(['Content-Type' => ['application/json']]);

        $this->mockClient->expects($this->once())
            ->method('send')
            ->with($mockRequest)
            ->willReturn($mockResponse);

        $this->mockLogger->expects($this->exactly(3))
            ->method('log');

        $result = $this->invokeProtectedMethod($this->baseService, 'send', ['GET', '/test']);
        
        $this->assertSame($mockResponse, $result);
    }

    public function testSendWithParameters(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $params = ['key1' => 'value1', 'key2' => 'value2'];
        $headers = ['Custom-Header' => 'custom-value'];
        $body = '{"test": "data"}';

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'POST',
                '/test?key1=value1&key2=value2',
                $this->callback(function ($actualHeaders) use ($headers) {
                    return $actualHeaders['Custom-Header'] === 'custom-value' 
                        && isset($actualHeaders['PayPal-Request-Id']);
                }),
                $body
            )
            ->willReturn($mockRequest);

        $this->mockClient->expects($this->once())
            ->method('getActionHash')
            ->willReturn('test-hash');

        $requestBodyStream = Utils::streamFor($body);
        $mockRequest->method('getBody')->willReturn($requestBodyStream);
        $mockRequest->method('getHeaders')->willReturn($headers);

        $this->mockClient->expects($this->once())
            ->method('send')
            ->with($mockRequest)
            ->willReturn($mockResponse);

        $result = $this->invokeProtectedMethod($this->baseService, 'send', ['POST', '/test', $params, $headers, $body]);
        
        $this->assertSame($mockResponse, $result);
    }

    public function testSendFiltersEmptyParameters(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $params = ['key1' => 'value1', 'key2' => '', 'key3' => null, 'key4' => 'value4'];

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'GET',
                '/test?key1=value1&key4=value4',
                $this->anything(),
                null
            )
            ->willReturn($mockRequest);

        $this->mockClient->method('getActionHash')->willReturn('test-hash');
        $requestBodyStream = Utils::streamFor('');
        $mockRequest->method('getBody')->willReturn($requestBodyStream);
        $mockRequest->method('getHeaders')->willReturn([]);
        $this->mockClient->method('send')->willReturn($mockResponse);

        $this->invokeProtectedMethod($this->baseService, 'send', ['GET', '/test', $params]);
    }

    public function testSendGeneratesUniqueRequestId(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $body = '{"test": "data"}';
        $path = '/test';

        $this->mockClient->expects($this->once())
            ->method('getActionHash')
            ->willReturn('action-hash');

        $expectedRequestId = md5($path . serialize($body) . 'action-hash');

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'POST',
                $path,
                ['PayPal-Request-Id' => $expectedRequestId],
                $body
            )
            ->willReturn($mockRequest);

        $requestBodyStream = Utils::streamFor($body);
        $mockRequest->method('getBody')->willReturn($requestBodyStream);
        $mockRequest->method('getHeaders')->willReturn([]);
        $this->mockClient->method('send')->willReturn($mockResponse);

        $this->invokeProtectedMethod($this->baseService, 'send', ['POST', $path, [], [], $body]);
    }

    public function testSendWithBasePathPrefix(): void
    {
        // Create a subclass to test basePath functionality
        $serviceWithBasePath = new class($this->mockClient) extends BaseService {
            protected string $basePath = '/v2/checkout';
        };

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->mockClient->expects($this->once())
            ->method('createRequest')
            ->with(
                'GET',
                '/v2/checkout/orders',
                $this->anything(),
                null
            )
            ->willReturn($mockRequest);

        $this->mockClient->method('getActionHash')->willReturn('test-hash');
        $requestBodyStream = Utils::streamFor('');
        $mockRequest->method('getBody')->willReturn($requestBodyStream);
        $mockRequest->method('getHeaders')->willReturn([]);
        $this->mockClient->method('send')->willReturn($mockResponse);

        $this->invokeProtectedMethod($serviceWithBasePath, 'send', ['GET', '/orders']);
    }

    public function testSendThrowsApiExceptionOnGuzzleException(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $guzzleException = new ClientException('Client error', new Request('GET', '/test'), new Response(400));

        $this->mockClient->method('createRequest')->willReturn($mockRequest);
        $this->mockClient->method('getActionHash')->willReturn('test-hash');
        $requestBodyStream = Utils::streamFor('');
        $mockRequest->method('getBody')->willReturn($requestBodyStream);
        $mockRequest->method('getHeaders')->willReturn([]);

        $this->mockClient->expects($this->once())
            ->method('send')
            ->with($mockRequest)
            ->willThrowException($guzzleException);

        $this->mockLogger->expects($this->atLeastOnce())
            ->method('log');

        $this->expectException(ApiException::class);

        $this->invokeProtectedMethod($this->baseService, 'send', ['GET', '/test']);
    }

    public function testSendLogsDebugInformation(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->mockClient->method('createRequest')->willReturn($mockRequest);
        $this->mockClient->method('getActionHash')->willReturn('test-hash');
        $this->mockClient->method('send')->willReturn($mockResponse);

        $requestBodyStream = Utils::streamFor('request-body-content');
        $mockRequest->method('getBody')->willReturn($requestBodyStream);
        $mockRequest->method('getHeaders')->willReturn(['Authorization' => ['Bearer token']]);

        $this->mockLogger->expects($this->exactly(3))
            ->method('log');

        $this->invokeProtectedMethod($this->baseService, 'send', ['POST', '/api/test']);
    }

    /**
     * Helper method to invoke protected methods for testing
     */
    private function invokeProtectedMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
}