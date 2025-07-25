<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\PayPalApi\Exception;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ApiExceptionTest extends TestCase
{
    private RequestInterface $mockRequest;
    private ResponseInterface $mockResponse;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRequest = new Request('POST', 'https://api.paypal.com/v2/checkout/orders');
        $this->mockRequest = $this->mockRequest->withHeader('Content-Type', 'application/json');
        $this->mockRequest = $this->mockRequest->withHeader('Authorization', 'Bearer token123');
    }

    public function testConstructorWithBasicError(): void
    {
        $this->mockResponse = new Response(400, [], json_encode([
            'message' => 'Invalid request',
            'details' => [
                [
                    'issue' => 'INVALID_PARAMETER',
                    'description' => 'The parameter is invalid'
                ]
            ]
        ]));
        
        $guzzleException = new ClientException('Bad Request', $this->mockRequest, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $this->assertEquals(400, $apiException->getCode());
        $this->assertStringContainsString('POST https://api.paypal.com/v2/checkout/orders returned: 400 Bad Request', $apiException->getMessage());
        $this->assertStringContainsString('Returned Message: Invalid request', $apiException->getMessage());
        $this->assertStringContainsString('Error Details:', $apiException->getMessage());
        $this->assertStringContainsString('curl -v -X POST', $apiException->getMessage());
    }

    public function testConstructorWithMinimalError(): void
    {
        $this->mockResponse = new Response(500, [], '{}');
        
        $guzzleException = new ClientException('Internal Server Error', $this->mockRequest, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $this->assertEquals(500, $apiException->getCode());
        $this->assertStringContainsString('POST https://api.paypal.com/v2/checkout/orders returned: 500 Internal Server Error', $apiException->getMessage());
        $this->assertStringContainsString('curl -v -X POST "https://api.paypal.com/v2/checkout/orders"', $apiException->getMessage());
    }

    public function testConstructorWithRequestBody(): void
    {
        $requestBody = json_encode(['test' => 'data']);
        $this->mockRequest = $this->mockRequest->withBody(\GuzzleHttp\Psr7\Utils::streamFor($requestBody));
        $this->mockResponse = new Response(422, [], '{"message":"Validation error"}');
        
        $guzzleException = new ClientException('Unprocessable Entity', $this->mockRequest, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $this->assertStringContainsString('-d {"test":"data"}', $apiException->getMessage());
    }

    public function testConstructorWithHeaders(): void
    {
        $this->mockResponse = new Response(401, [], '{}');
        
        $guzzleException = new ClientException('Unauthorized', $this->mockRequest, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $message = $apiException->getMessage();
        $this->assertStringContainsString('-H "Content-Type: application/json"', $message);
        $this->assertStringContainsString('-H "Authorization: Bearer token123"', $message);
    }

    public function testShouldDisplayReturnsTrue(): void
    {
        $this->mockResponse = new Response(400, [], '{}');
        $guzzleException = new ClientException('Bad Request', $this->mockRequest, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $this->assertTrue($apiException->shouldDisplay());
    }

    public function testGetErrorDescriptionWithDetails(): void
    {
        $errorBody = [
            'message' => 'Main error message',
            'details' => [
                [
                    'issue' => 'INVALID_PARAMETER',
                    'description' => 'Parameter description from details'
                ]
            ]
        ];
        
        $this->mockResponse = new Response(400, [], json_encode($errorBody));
        $guzzleException = new ClientException('Bad Request', $this->mockRequest, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $description = $apiException->getErrorDescription();
        $this->assertEquals('Parameter description from details', $description);
    }

    public function testGetErrorDescriptionFallbackToMessage(): void
    {
        $errorBody = [
            'message' => 'Fallback error message',
            'details' => [
                [
                    'issue' => 'INVALID_PARAMETER',
                    'description' => null // No description, should fallback
                ]
            ]
        ];
        
        $this->mockResponse = new Response(400, [], json_encode($errorBody));
        $guzzleException = new ClientException('Bad Request', $this->mockRequest, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $description = $apiException->getErrorDescription();
        $this->assertEquals('Fallback error message', $description);
    }

    public function testGetErrorDescriptionWithInvalidJson(): void
    {
        $this->mockResponse = new Response(400, [], 'invalid json');
        $guzzleException = new ClientException('Bad Request', $this->mockRequest, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $description = $apiException->getErrorDescription();
        $this->assertEquals('', $description);
    }

    public function testGetErrorIssueWithValidData(): void
    {
        $errorBody = [
            'details' => [
                [
                    'issue' => 'INVALID_CURRENCY_CODE',
                    'description' => 'Currency code is invalid'
                ]
            ]
        ];
        
        $this->mockResponse = new Response(400, [], json_encode($errorBody));
        $guzzleException = new ClientException('Bad Request', $this->mockRequest, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $issue = $apiException->getErrorIssue();
        $this->assertEquals('INVALID_CURRENCY_CODE', $issue);
    }

    public function testGetErrorIssueWithMissingDetails(): void
    {
        $errorBody = ['message' => 'Error without details'];
        
        $this->mockResponse = new Response(400, [], json_encode($errorBody));
        $guzzleException = new ClientException('Bad Request', $this->mockRequest, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $issue = $apiException->getErrorIssue();
        $this->assertEquals('', $issue);
    }

    public function testGetErrorIssueWithEmptyDetails(): void
    {
        $errorBody = ['details' => []];
        
        $this->mockResponse = new Response(400, [], json_encode($errorBody));
        $guzzleException = new ClientException('Bad Request', $this->mockRequest, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $issue = $apiException->getErrorIssue();
        $this->assertEquals('', $issue);
    }

    public function testGetErrorIssueWithMissingIssueField(): void
    {
        $errorBody = [
            'details' => [
                [
                    'description' => 'Description without issue field'
                ]
            ]
        ];
        
        $this->mockResponse = new Response(400, [], json_encode($errorBody));
        $guzzleException = new ClientException('Bad Request', $this->mockRequest, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $issue = $apiException->getErrorIssue();
        $this->assertEquals('', $issue);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('httpMethodsProvider')]
    public function testDifferentHttpMethods(string $method): void
    {
        $request = new Request($method, 'https://api.paypal.com/test');
        $this->mockResponse = new Response(400, [], '{}');
        
        $guzzleException = new ClientException('Error', $request, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $this->assertStringContainsString("$method https://api.paypal.com/test returned:", $apiException->getMessage());
        $this->assertStringContainsString("curl -v -X $method", $apiException->getMessage());
    }

    public static function httpMethodsProvider(): array
    {
        return [
            ['GET'],
            ['POST'],
            ['PUT'],
            ['PATCH'],
            ['DELETE'],
        ];
    }

    public function testComplexErrorResponse(): void
    {
        $complexError = [
            'name' => 'VALIDATION_ERROR',
            'message' => 'Invalid request - see details',
            'debug_id' => 'debug123',
            'details' => [
                [
                    'field' => 'purchase_units[0].amount.currency_code',
                    'value' => 'INVALID',
                    'issue' => 'CURRENCY_NOT_SUPPORTED',
                    'description' => 'Currency is not supported'
                ],
                [
                    'field' => 'purchase_units[0].amount.value',
                    'value' => '-10.00',
                    'issue' => 'INVALID_PARAMETER_VALUE',
                    'description' => 'Amount value cannot be negative'
                ]
            ],
            'links' => [
                [
                    'href' => 'https://developer.paypal.com/docs/api/orders/v2/',
                    'rel' => 'information_link'
                ]
            ]
        ];
        
        $this->mockResponse = new Response(400, [], json_encode($complexError));
        $guzzleException = new ClientException('Bad Request', $this->mockRequest, $this->mockResponse);
        $apiException = new ApiException($guzzleException);
        
        $message = $apiException->getMessage();
        $this->assertStringContainsString('Invalid request - see details', $message);
        $this->assertStringContainsString('Error Details:', $message);
        $this->assertStringContainsString('CURRENCY_NOT_SUPPORTED', $message);
        $this->assertStringContainsString('INVALID_PARAMETER_VALUE', $message);
        $this->assertStringContainsString('"name":"VALIDATION_ERROR"', $message);
        $this->assertStringContainsString('"debug_id":"debug123"', $message);
        
        // Test specific error extraction
        $description = $apiException->getErrorDescription();
        $this->assertEquals('Currency is not supported', $description);
        
        $issue = $apiException->getErrorIssue();
        $this->assertEquals('CURRENCY_NOT_SUPPORTED', $issue);
    }
}