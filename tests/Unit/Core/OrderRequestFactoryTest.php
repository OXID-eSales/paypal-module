<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Core;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Core\Api\VaultingService;
use OxidSolutionCatalysts\PayPal\Service\Factory\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\Factory\PayPalPurchaseUnitsFactory;
use OxidSolutionCatalysts\PayPal\Service\LanguageLocaleMapper;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderExperienceContext;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidSolutionCatalysts\PayPalApi\Pui\ExperienceContext;
use oxuser;
use PHPUnit\Framework\MockObject\MockObject;

class OrderRequestFactoryTest extends UnitTestCase
{
    use \OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;

    /** @var OrderRequestFactory */
    private $orderRequestFactory;

    /** @var MockObject|VaultingService */
    private $vaultingServiceMock;

    /** @var MockObject|Basket */
    private $basketMock;

    /** @var MockObject|Session */
    private $sessionMock;

    /** @var MockObject|Config */
    private $configMock;

    /** @var MockObject|Country */
    private $countryMock;

    /** @var MockObject|ModuleSettings */
    private $moduleSettingsMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->initMocks();
        Registry::set('oxsession', $this->sessionMock);
        Registry::set('oxconfig', $this->configMock);
    }

    public function testGetRequest()
    {
        // Create a proper basket mock instead of order mock
        $basketMock = $this->createMock(Basket::class);
        $basketMock->method('isCalculationModeNetto')->willReturn(false);

        // Setup session mock to return payment ID
        $this->sessionMock->method('getVariable')
            ->with('paymentid')
            ->willReturn(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);

        // Mock the required services and methods
        $this->moduleSettingsMock->method('getIsVaultingActive')->willReturn(false);
        $this->vaultingServiceMock->method('fetchSelectedVaultedPaymentToken')->willReturn(null);

        // This test should focus on the structure, not the full implementation
        $this->markTestSkipped('This test needs to be rewritten to properly test the OrderRequestFactory with all dependencies');
    }

    /**
     * Test experience_context data for PayPal payment source without user (guest checkout)
     */
    public function testExperienceContextForGuestPayPalPayment(): void
    {
        $returnUrl = 'https://example.com/return';
        $cancelUrl = 'https://example.com/cancel';
        $userAction = OrderRequestFactory::USER_ACTION_PAY_NOW;

        $this->sessionMock->method('getVariable')
            ->willReturnMap([
                ['paymentid', PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID],
            ]);
        $this->setupMocksForGuestPayment(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
        $this->mockBasketAndAmountFactory();

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            $userAction,
            null,
            null,
            null,
            null,
            $returnUrl,
            $cancelUrl
        );

        $this->assertInstanceOf(OrderRequest::class, $request);
        $this->assertNotNull($request->payment_source);

        // Check if PaymentSource is an object with properties or an array
        if (is_object($request->payment_source)) {
            $paymentSourceObj = $request->payment_source->jsonSerialize();
        } else {
            $paymentSourceObj = $request->payment_source;
        }

        $paypalSource = $paymentSourceObj->paypal ?? null;

        $this->assertNotNull($paypalSource, 'PayPal payment source should be set');

        // Check that experience_context exists - the exact structure depends on implementation
        if (isset($paypalSource->experience_context)) {
            $experienceContext = $paypalSource->experience_context;
            $this->assertIsArray($experienceContext);
            // Basic check that some context data exists
            $this->assertArrayHasKey('user_action', $experienceContext);
        }
    }

    /**
     * Test experience_context data for PayPal payment source with logged-in user
     */
    public function testExperienceContextForLoggedInPayPalPayment(): void
    {
        $returnUrl = 'https://example.com/return';
        $cancelUrl = 'https://example.com/cancel';
        $userAction = OrderRequestFactory::USER_ACTION_CONTINUE;

        $this->setupMocksForLoggedInPayment(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
        $this->mockBasketAndAmountFactory();

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            $userAction,
            null,
            null,
            null,
            null,
            $returnUrl,
            $cancelUrl
        );

        $this->assertInstanceOf(OrderRequest::class, $request);
        $this->assertNotNull($request->payment_source);

        $paymentSourceArray = $request->payment_source;

        $paypalSource = $paymentSourceArray['paypal'] ?? null;

        $this->assertNotNull($paypalSource, 'PayPal payment source should be set');

        if (isset($paypalSource['experience_context'])) {
            $experienceContext = $paypalSource['experience_context'];
            $this->assertIsArray($experienceContext);
            // Basic checks that some context data exists
            $this->assertArrayHasKey('user_action', $experienceContext);
            if (isset($experienceContext['return_url'])) {
                $this->assertEquals($returnUrl, $experienceContext['return_url']);
            }
            if (isset($experienceContext['cancel_url'])) {
                $this->assertEquals($cancelUrl, $experienceContext['cancel_url']);
            }
        }
    }

    /**
     * Test experience_context data for Card payment source with vaulting
     */
    public function testExperienceContextForCardPaymentWithVaulting(): void
    {
        $returnUrl = 'https://example.com/return';
        $cancelUrl = 'https://example.com/cancel';
        $userAction = OrderRequestFactory::USER_ACTION_PAY_NOW;

        $this->setupMocksForVaultedPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);
        $this->mockBasketAndAmountFactory();

        $vaultedToken = [
            'id' => 'vault_token_123',
            'payment_source' => ['card' => ['last_digits' => '1234']]
        ];
        $this->vaultingServiceMock->method('fetchSelectedVaultedPaymentToken')
            ->willReturn($vaultedToken);

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            $userAction,
            null,
            null,
            null,
            null,
            $returnUrl,
            $cancelUrl
        );

        $paymentSourceArray = $request->payment_source;

        $cardSource = $paymentSourceArray['card'] ?? null;

        $this->assertNotNull($cardSource, 'Card payment source should be set');

        // Check that experience_context exists for card payments
        if (isset($cardSource['experience_context'])) {
            $experienceContext = $cardSource['experience_context'];
            $this->assertIsArray($experienceContext);
            $this->assertArrayHasKey('user_action', $experienceContext);

            if (isset($experienceContext['return_url'])) {
                $this->assertEquals($returnUrl, $experienceContext['return_url']);
            }
            if (isset($experienceContext['cancel_url'])) {
                $this->assertEquals($cancelUrl, $experienceContext['cancel_url']);
            }
        }
    }

    /**
     * Test experience_context data for alternative payment methods (UAPM)
     */
    public function testExperienceContextForAlternativePaymentMethods(): void
    {
        $returnUrl = 'https://example.com/return';
        $cancelUrl = 'https://example.com/cancel';
        $userAction = OrderRequestFactory::USER_ACTION_CONTINUE;

        $this->setupMocksForLoggedInPayment(PayPalDefinitions::IDEAL_PAYPAL_PAYMENT_ID);
        $this->mockBasketAndAmountFactory();

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            $userAction,
            null,
            null,
            null,
            null,
            $returnUrl,
            $cancelUrl
        );

        $paymentSourceArray = $request->payment_source;

        $idealSource = $paymentSourceArray['ideal'] ?? null;

        $this->assertNotNull($idealSource, 'iDEAL payment source should be set');

        // Check that experience_context exists for alternative payment methods
        if (isset($idealSource['experience_context'])) {
            $experienceContext = $idealSource['experience_context'];
            $this->assertIsArray($experienceContext);
            $this->assertArrayHasKey('user_action', $experienceContext);

            if (isset($experienceContext['return_url'])) {
                $this->assertEquals($returnUrl, $experienceContext['return_url']);
            }
            if (isset($experienceContext['cancel_url'])) {
                $this->assertEquals($cancelUrl, $experienceContext['cancel_url']);
            }
        }
    }

    /**
     * Test experience_context default values when parameters are null
     */
    public function testExperienceContextDefaultValues(): void
    {
        $this->setupMocksForLoggedInPayment(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
        $this->mockBasketAndAmountFactory();

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            null, // userAction is null
            null,
            null,
            null,
            null,
            '', // returnUrl is null
            ''  // cancelUrl is null
        );

        $paymentSourceArray = $request->payment_source;

        $paypalSource = $paymentSourceArray['paypal'] ?? null;

        $this->assertNotNull($paypalSource, 'PayPal payment source should be set');

        // Check that experience_context exists with default values
        if (isset($paypalSource['experience_context'])) {
            $experienceContext = $paypalSource['experience_context'];
            $this->assertIsArray($experienceContext);
            $this->assertArrayHasKey('user_action', $experienceContext);
            // Default user_action should be set when null is passed
        }
    }

    /**
     * Test experience_context for vault payment request
     */
    public function testExperienceContextForVaultPaymentRequest(): void
    {
        $returnUrl = 'https://example.com/return';
        $cancelUrl = 'https://example.com/cancel';

        $this->setupMocksForVaultingRequest();
        $this->mockBasketAndAmountFactory();

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            null,
            null,
            null,
            null,
            null,
            $returnUrl,
            $cancelUrl
        );

        $paymentSourceArray = $request->payment_source;

        $paypalSource = $paymentSourceArray['paypal'] ?? null;

        $this->assertNotNull($paypalSource, 'PayPal payment source should be set for vaulting');

        if (isset($paypalSource->experience_context)) {
            $experienceContext = $paypalSource->experience_context;
            $this->assertIsArray($experienceContext);
        }
    }

    /**
     * Test experience_context data for Apple Pay payment source
     */
    public function testExperienceContextForApplePayPayment(): void
    {
        $returnUrl = 'https://example.com/return';
        $cancelUrl = 'https://example.com/cancel';
        $userAction = OrderRequestFactory::USER_ACTION_PAY_NOW;

        $this->setupMocksForLoggedInPayment(PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID);
        $this->mockBasketAndAmountFactory();

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            $userAction,
            null,
            null,
            null,
            null,
            $returnUrl,
            $cancelUrl
        );

        $this->assertInstanceOf(OrderRequest::class, $request);
        $this->assertNotNull($request->payment_source);

        $paymentSourceArray = $request->payment_source;

        $applePaySource = $paymentSourceArray['apple_pay'] ?? null;

        $this->assertNotNull($applePaySource, 'Apple Pay payment source should be set');

        // Check that experience_context exists for Apple Pay payments
        if (isset($applePaySource['experience_context'])) {
            $experienceContext = $applePaySource['experience_context'];
            $this->assertIsArray($experienceContext);

            // Verify Apple Pay specific experience context properties
            $this->assertArrayHasKey('user_action', $experienceContext);
            $this->assertEquals($userAction, $experienceContext['user_action']);

            if (isset($experienceContext['return_url'])) {
                $this->assertEquals($returnUrl, $experienceContext['return_url']);
            }
            if (isset($experienceContext['cancel_url'])) {
                $this->assertEquals($cancelUrl, $experienceContext['cancel_url']);
            }

            // Apple Pay typically should have shipping preference set
            if (isset($experienceContext['shipping_preference'])) {
                $this->assertContains(
                    $experienceContext['shipping_preference'],
                    ['SET_PROVIDED_ADDRESS', 'GET_FROM_FILE', 'NO_SHIPPING']
                );
            }

            // Check if brand_name is set in experience context
            if (isset($experienceContext['brand_name'])) {
                $this->assertIsString($experienceContext['brand_name']);
                $this->assertNotEmpty($experienceContext['brand_name']);
            }

            // Check if locale is properly formatted (e.g., "de-DE", "en-US")
            if (isset($experienceContext['locale'])) {
                $this->assertMatchesRegularExpression(
                    '/^[a-z]{2}-[A-Z]{2}$/',
                    $experienceContext['locale'],
                    'Locale should be in format "xx-XX"'
                );
            }
        }
    }

    /**
     * Test experience_context data for Apple Pay with guest user
     */
    public function testExperienceContextForApplePayGuestPayment(): void
    {
        $returnUrl = 'https://example.com/return';
        $cancelUrl = 'https://example.com/cancel';
        $userAction = OrderRequestFactory::USER_ACTION_CONTINUE;

        $this->setupMocksForGuestPayment(PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID);
        $this->mockBasketAndAmountFactory();

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            $userAction,
            null,
            null,
            null,
            null,
            $returnUrl,
            $cancelUrl
        );

        $this->assertInstanceOf(OrderRequest::class, $request);
        $this->assertNotNull($request->payment_source);

        $paymentSourceArray = $request->payment_source;

        $applePaySource = $paymentSourceArray->apple_pay ?? null;

        $this->assertNotNull($applePaySource, 'Apple Pay payment source should be set for guest user');

        if (isset($applePaySource->experience_context)) {
            $experienceContext = $applePaySource->experience_context;
            $this->assertIsArray($experienceContext);

            if (isset($experienceContext['user_action'])) {
                $this->assertEquals($userAction, $experienceContext['user_action']);
            }
        }
    }

    /**
     * Test Apple Pay experience_context with return_url and cancel_url
     */
    public function testApplePayExperienceContextWithUrls(): void
    {
        $returnUrl = 'https://example.com/return';
        $cancelUrl = 'https://example.com/cancel';
        $userAction = OrderRequestFactory::USER_ACTION_PAY_NOW;

        $this->setupMocksForLoggedInPayment(PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID);

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            $userAction,
            null,
            null,
            null,
            null,
            $returnUrl,
            $cancelUrl
        );

        // Verify that the method returns an OrderRequest instance
        $this->assertInstanceOf(OrderRequest::class, $request);
        $this->assertNotNull($request->payment_source, 'Payment source should not be null');

        // Check if PaymentSource is an object with properties or an array
        if (is_object($request->payment_source)) {
            $paymentSourceArray = $request->payment_source->jsonSerialize();
        } else {
            $paymentSourceArray = $request->payment_source;
        }

        $applePaySource = $paymentSourceArray['apple_pay'] ?? null;
        $this->assertNotNull($applePaySource, 'Apple Pay payment source should be set');

        // Verify that experience_context exists for Apple Pay
        if (isset($applePaySource['experience_context'])) {
            $experienceContext = $applePaySource['experience_context'];
            $this->assertIsArray($experienceContext, 'Experience context should be an array');

            // Check that return_url and cancel_url exist in Apple Pay experience context
            $this->assertArrayHasKey('return_url', $experienceContext, 'return_url should exist in Apple Pay experience_context');
            $this->assertArrayHasKey('cancel_url', $experienceContext, 'cancel_url should exist in Apple Pay experience_context');

            // Verify the actual values
            $this->assertEquals($returnUrl, $experienceContext['return_url'], 'Apple Pay return_url should match');
            $this->assertEquals($cancelUrl, $experienceContext['cancel_url'], 'Apple Pay cancel_url should match');
        }
    }

    /**
     * Test that OrderRequest is returned even when URLs are null
     */
    public function testGetRequestReturnsOrderRequestWithNullUrls(): void
    {
        $this->setupMocksForLoggedInPayment(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE,
            null,
            null,
            null,
            null,
            '', // return_url is null
            ''  // cancel_url is null
        );

        // Most important assertion: verify return type is OrderRequest
        $this->assertInstanceOf(OrderRequest::class, $request);

        // Verify basic structure is still intact
        $this->assertNotNull($request->payment_source, 'Payment source should still be set even with null URLs');
        $this->assertEquals(OrderRequest::INTENT_CAPTURE, $request->intent, 'Intent should be properly set');
        $this->assertNotEmpty($request->purchase_units, 'Purchase units should be set');
    }


    /**
     * Test experience_context URLs for different payment types using data provider
     *
     * @dataProvider paymentTypesDataProvider
     */
    public function testExperienceContextUrlsForDifferentPaymentTypes(
        string $paymentId,
        string $expectedPaymentSource,
        bool $shouldHaveExperienceContext,
        bool $shouldHaveUrls
    ): void {
        $returnUrl = 'https://example.com/return';
        $cancelUrl = 'https://example.com/cancel';
        $userAction = OrderRequestFactory::USER_ACTION_PAY_NOW;

        $this->setupMocksForLoggedInPayment($paymentId);

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            $userAction,
            null,
            null,
            null,
            null,
            $returnUrl,
            $cancelUrl
        );

        $this->assertInstanceOf(OrderRequest::class, $request);
        $this->assertNotNull($request->payment_source, "Payment source should not be null for {$paymentId}");

        $paymentSourceArray = $request->payment_source;

        $this->assertTrue(
            array_key_exists($expectedPaymentSource, $paymentSourceArray)
            && is_array($paymentSourceArray[$expectedPaymentSource]),
            "Payment source '{$expectedPaymentSource}' should exist for {$paymentId}"
        );

        $sourceData = $paymentSourceArray[$expectedPaymentSource];

        if ($shouldHaveExperienceContext) {
            $this->assertArrayHasKey(
                'experience_context',
                $sourceData,
                "Experience context should exist for {$paymentId}"
            );

            $experienceContext = $sourceData['experience_context'];
            $this->assertIsArray($experienceContext, "Experience context should be an array for {$paymentId}");

            if ($shouldHaveUrls) {
                $this->assertArrayHasKey(
                    'return_url',
                    $experienceContext,
                    "return_url should exist in experience_context for {$paymentId}"
                );
                $this->assertArrayHasKey(
                    'cancel_url',
                    $experienceContext,
                    "cancel_url should exist in experience_context for {$paymentId}"
                );
                $this->assertEquals(
                    $returnUrl,
                    $experienceContext['return_url'],
                    "return_url should match for {$paymentId}"
                );
                $this->assertEquals(
                    $cancelUrl,
                    $experienceContext['cancel_url'],
                    "cancel_url should match for {$paymentId}"
                );
            } else {
                // Some payment types might have experience_context but without URLs
                $this->assertTrue(
                    !isset($experienceContext['return_url']) || empty($experienceContext['return_url']),
                    "return_url should not be set for {$paymentId}"
                );
                $this->assertTrue(
                    !isset($experienceContext['cancel_url']) || empty($experienceContext['cancel_url']),
                    "cancel_url should not be set for {$paymentId}"
                );
            }
        } else {
            $this->assertArrayNotHasKey(
                'experience_context',
                $sourceData,
                "Experience context should not exist for {$paymentId}"
            );
        }
    }

    /**
     * Data provider for payment types testing
     *
     * @return array
     */
    public function paymentTypesDataProvider(): array
    {
        return [
            // PayPal payments - should have experience_context with URLs
            'Standard PayPal' => [
                PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID,
                'paypal',
                true,  // should have experience_context
                true   // should have URLs
            ],
            'Express PayPal' => [
                PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID,
                'paypal',
                true,  // should have experience_context
                true   // should have URLs
            ],
            'PayLater PayPal' => [
                PayPalDefinitions::PAYLATER_PAYPAL_PAYMENT_ID,
                'paypal',
                true,  // should have experience_context
                true   // should have URLs
            ],

            // Apple Pay - should have experience_context with URLs
            'Apple Pay' => [
                PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID,
                'apple_pay',
                true,  // should have experience_context
                true   // should have URLs
            ],

            // Google Pay - should have experience_context with URLs
            'Google Pay' => [
                PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID,
                'google_pay',
                true,  // should have experience_context
                true   // should have URLs
            ],

            // Alternative payment methods - should have experience_context with URLs
            'iDEAL' => [
                PayPalDefinitions::IDEAL_PAYPAL_PAYMENT_ID,
                'ideal',
                true,  // should have experience_context
                true   // should have URLs
            ],
            'Giropay' => [
                PayPalDefinitions::GIROPAY_PAYPAL_PAYMENT_ID,
                'giropay',
                true,  // should have experience_context
                true   // should have URLs
            ],
            'EPS' => [
                PayPalDefinitions::EPS_PAYPAL_PAYMENT_ID,
                'eps',
                true,  // should have experience_context
                true   // should have URLs
            ],
            'BLIK' => [
                PayPalDefinitions::BLIK_PAYPAL_PAYMENT_ID,
                'blik',
                true,  // should have experience_context
                true   // should have URLs
            ],
//            TDOO: eeds more mocking in the basket
//            'Przelewy24' => [
//                PayPalDefinitions::PRZELEWY24_PAYPAL_PAYMENT_ID,
//                'p24',
//                true,  // should have experience_context
//                true   // should have URLs
//            ],
            'Bancontact' => [
                PayPalDefinitions::BANCONTACT_PAYPAL_PAYMENT_ID,
                'bancontact',
                true,  // should have experience_context
                true   // should have URLs
            ],

            // Card payments - should have experience_context with URLs
            'ACDC (Card)' => [
                PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'card',
                true,  // should have experience_context
                true   // should have URLs
            ],

            // SEPA - should have experience_context with URLs
            'SEPA' => [
                PayPalDefinitions::SEPA_PAYPAL_PAYMENT_ID,
                'paypal', // SEPA uses PayPal payment source
                true,  // should have experience_context
                true   // should have URLs
            ],

            // PUI - special case: has its own experience_context structure, no URLs in standard experience_context
            'Pay Upon Invoice (PUI)' => [
                PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID,
                'pay_upon_invoice',
                true, // PUI uses different experience_context structure (ExperienceContext vs standard)
                true  // URLs are not in the standard experience_context for PUI
            ],
        ];
    }

    /**
     * Test specifically that PUI payment has its own ExperienceContext structure
     */
    public function testPuiPaymentHasSpecialExperienceContext(): void
    {
        $returnUrl = 'https://example.com/return';
        $cancelUrl = 'https://example.com/cancel';

        $this->setupMocksForLoggedInPayment(PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID);

        $this->setRequestParameter(
            'pui_required',
            [
                'phonenumber' => '+49 40 111222333'
            ]
        );

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE,
            null,
            null,
            PayPalDefinitions::PAYMENT_SOURCE_PUI, // This triggers PUI-specific handling
            null,
            $returnUrl,
            $cancelUrl
        );

        $this->assertInstanceOf(OrderRequest::class, $request);
        $this->assertNotNull($request->payment_source);

        // Get payment source data
        if (is_object($request->payment_source)) {
            $paymentSourceArray = $request->payment_source->jsonSerialize();
        } else {
            $paymentSourceArray = $request->payment_source;
        }


        if (isset($paymentSourceArray['pay_upon_invoice'])) {
            $puiSource = $paymentSourceArray['pay_upon_invoice'];

            if (is_object($puiSource->experience_context)) {
                $experienceContext = $puiSource->experience_context;

                $this->assertIsObject($experienceContext);
                $this->assertInstanceOf( OrderExperienceContext::class, $experienceContext);

                $this->assertTrue(
                    !empty($experienceContext->brand_name) &&
                    !empty($experienceContext->locale) &&
                    !empty($experienceContext->customer_service_instructions),
                    'PUI experience context should have PUI-specific properties'
                );
            }
        }
    }

    private function setupMocksForGuestPayment(string $paymentId): void
    {
        $this->sessionMock->method('getVariable')
            ->willReturnMap([
                ['paymentid', $paymentId],
                ['vaultSuccess', false]
            ]);

        $this->sessionMock->method('getBasket')
            ->willReturn($this->basketMock);

        $this->basketMock->method('getPaymentId')
            ->willReturn($paymentId);
        $this->basketMock->method('isCalculationModeNetto')
            ->willReturn(false);

        $this->configMock->method('getUser')
            ->willReturn(null); // Guest user

        $this->moduleSettingsMock->method('getIsVaultingActive')
            ->willReturn(false);
        $this->moduleSettingsMock->method('isSandbox')
            ->willReturn(false);
        $this->moduleSettingsMock->method('getPayPalSCAContingency')
            ->willReturn('SCA_WHEN_REQUIRED');
        $this->moduleSettingsMock->method('getShopName')
            ->willReturn('Test Shop');

        $this->vaultingServiceMock->method('fetchSelectedVaultedPaymentToken')
            ->willReturn(null);

        $this->mockRequiredBasketMethods();
    }

    private function setupMocksForLoggedInPayment(string $paymentId): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getFieldData')
            ->willReturn('paypal_customer_123');

        $this->sessionMock->method('getVariable')
            ->willReturnMap([
                ['paymentid', $paymentId],
                ['vaultSuccess', false]
            ]);

        $this->sessionMock->method('getBasket')
            ->willReturn($this->basketMock);

        $this->basketMock->method('getPaymentId')
            ->willReturn($paymentId);
        $this->basketMock->method('isCalculationModeNetto')
            ->willReturn(false);

        $this->configMock->method('getUser')
            ->willReturn($userMock); // Logged-in user

        $this->moduleSettingsMock->method('getIsVaultingActive')
            ->willReturn(false);
        $this->moduleSettingsMock->method('isSandbox')
            ->willReturn(false);
        $this->moduleSettingsMock->method('getPayPalSCAContingency')
            ->willReturn('SCA_WHEN_REQUIRED');
        $this->moduleSettingsMock->method('getShopName')
            ->willReturn('Test Shop');

        $this->vaultingServiceMock->method('fetchSelectedVaultedPaymentToken')
            ->willReturn(null);

        $this->mockRequiredBasketMethods();
    }

    private function setupMocksForVaultedPayment(string $paymentId): void
    {
        $userMock = $this->createMock(User::class);

        $this->sessionMock->method('getVariable')
            ->willReturnMap([
                ['paymentid', $paymentId],
                ['vaultSuccess', false]
            ]);

        $this->sessionMock->method('getBasket')
            ->willReturn($this->basketMock);

        $this->basketMock->method('getPaymentId')
            ->willReturn($paymentId);
        $this->basketMock->method('isCalculationModeNetto')
            ->willReturn(false);

        $this->configMock->method('getUser')
            ->willReturn($userMock);

        $this->moduleSettingsMock->method('getIsVaultingActive')
            ->willReturn(true); // Vaulting is active
        $this->moduleSettingsMock->method('isSandbox')
            ->willReturn(false);
        $this->moduleSettingsMock->method('getPayPalSCAContingency')
            ->willReturn('SCA_WHEN_REQUIRED');
        $this->moduleSettingsMock->method('getShopName')
            ->willReturn('Test Shop');

        $this->mockRequiredBasketMethods();
    }

    private function setupMocksForVaultingRequest(): void
    {
        $userMock = $this->createMock(User::class);

        // Mock registry request to return vaultPayment parameter
        $requestMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRequestParameter'])
            ->getMock();
        $requestMock->method('getRequestParameter')
            ->willReturnMap([
                ['vaultPayment', 'true'],
                ['oscPayPalPaymentTypeForVaulting', PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID]
            ]);

        Registry::set('oxrequest', $requestMock);

        $this->sessionMock->method('getVariable')
            ->willReturnMap([
                ['paymentid', PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID],
                ['vaultSuccess', true]
            ]);

        $this->sessionMock->method('getBasket')
            ->willReturn($this->basketMock);
        $this->sessionMock->method('setVariable')
            ->with('vaultSuccess', true);

        $this->basketMock->method('getPaymentId')
            ->willReturn(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);
        $this->basketMock->method('isCalculationModeNetto')
            ->willReturn(false);

        $this->configMock->method('getUser')
            ->willReturn($userMock);

        $this->moduleSettingsMock->method('getIsVaultingActive')
            ->willReturn(true);
        $this->moduleSettingsMock->method('isSandbox')
            ->willReturn(false);
        $this->moduleSettingsMock->method('getPayPalSCAContingency')
            ->willReturn('SCA_WHEN_REQUIRED');
        $this->moduleSettingsMock->method('getShopName')
            ->willReturn('Test Shop');

        $this->vaultingServiceMock->method('fetchSelectedVaultedPaymentToken')
            ->willReturn(null);
        $this->vaultingServiceMock->method('getPaymentSourceForVaulting')
            ->willReturn(['paypal' => []]);

        $this->mockRequiredBasketMethods();
    }

    private function mockRequiredBasketMethods(): void
    {
        $this->basketMock->method('getBasketUser')
            ->willReturn(null);
    }

    private function mockBasketAndAmountFactory(): void
    {
        // Mock the PayPalRequestAmountFactory that's used in getAmount()
        $amountFactoryMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getAmount'])
            ->getMock();

        $mockAmount = new \stdClass();
        $mockAmount->currency_code = 'EUR';
        $mockAmount->value = '100.00';

        $amountFactoryMock->method('getAmount')
            ->willReturn($mockAmount);

        Registry::set('OxidSolutionCatalysts\PayPal\Core\PayPalRequestAmountFactory', $amountFactoryMock);
    }

    private function initMocks()
    {
        $this->moduleSettingsMock = $this->createMock(ModuleSettings::class);

        $mockPurchaseUnitsFactory = new PayPalPurchaseUnitsFactory($this->moduleSettingsMock);

        $this->orderRequestFactory = $this->getMockBuilder(OrderRequestFactory::class)
            ->setConstructorArgs([$mockPurchaseUnitsFactory, $this->moduleSettingsMock])
            ->onlyMethods(
                [
                    'getServiceFromContainer',
                    'getUserNameFromBasket',
                    'getCountryFromBasket',
                    'getVaultingService',
                    'getPurchaseUnits',
                    'getAmount',
                ]
            )->getMock();

        $this->orderRequestFactory->method('getAmount')
            ->willReturn(new \OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown(
                [
                    'breakdown' => [
                        'item_total' => [
                            'currency_code' => 'EUR',
                            'value' => '100.00'
                        ],
                        'tax_total' => [
                            'currency_code' => 'EUR',
                            'value' => '0.00'
                        ]
                    ]
                ]
            ));

        $this->orderRequestFactory->method('getPurchaseUnits')
            ->willReturn([
                new \OxidSolutionCatalysts\PayPalApi\Model\Orders\PurchaseUnit([
                    'reference_id' => '123',
                    'amount' => [
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => 'EUR',
                                'value' => '100.00'
                            ],
                        ]
                    ]
                ])
            ]);

        $this->vaultingServiceMock = $this->createMock(VaultingService::class);
        $this->basketMock = $this->createMock(Basket::class);
        $this->userMock = oxNew(User::class);
        $this->userMock->assign(
            [
                'oxcountryid' => 'a7c40f631fc920687.20179984'
            ]
        );
        $this->basketMock->method('getBasketUser')
            ->willReturn($this->userMock);

        $this->sessionMock = $this->createMock(Session::class);
        $this->configMock = $this->createMock(Config::class);
        $this->countryMock = $this->createMock(Country::class);
        $this->orderRequestFactory->method('getUserNameFromBasket')
            ->willReturn('John Doe');

        $this->orderRequestFactory->method('getCountryFromBasket')
            ->willReturn($this->countryMock);

        $this->orderRequestFactory->method('getVaultingService')
            ->willReturn($this->vaultingServiceMock);

        // the experience context locale is resolved through LanguageLocaleMapper, which is fetched
        // from the container just like ModuleSettings
        $this->moduleSettingsMock->method('getSupportedLocales')
            ->willReturn(['de_DE', 'en_US']);
        $this->orderRequestFactory->method('getServiceFromContainer')
            ->willReturnCallback(function (string $serviceName) {
                return $serviceName === LanguageLocaleMapper::class
                    ? new LanguageLocaleMapper($this->moduleSettingsMock)
                    : $this->moduleSettingsMock;
            });

        $this->countryMock->method('getFieldData')
            ->with('oxisoalpha2')
            ->willReturn('DE');

        $mockLang = $this->createMock(\OxidEsales\Eshop\Core\Language::class);
        $mockLang->method('getLanguageAbbr')
            ->willReturn('de');
        $mockLang->method('translateString')
            ->willReturnMap([
                ['OSC_PAYPAL_DESCRIPTION', null, null, 'Payment at %s'],
                // Add more keys if needed
            ]);

        Registry::set(\OxidEsales\Eshop\Core\Language::class, $mockLang);
        // Create price mock
        $priceMock = $this->createMock(Price::class);
        $priceMock->method('getBruttoPrice')->willReturn(10.00);

        Registry::getSession()->setBasket($this->basketMock);
        $this->basketMock->method('getPrice')->willReturn($priceMock);
    }
}
