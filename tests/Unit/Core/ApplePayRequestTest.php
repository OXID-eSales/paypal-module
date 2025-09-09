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
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Core\Api\VaultingService;
use OxidSolutionCatalysts\PayPal\Service\Factory\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\Factory\PayPalPurchaseUnitsFactory;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use PHPUnit\Framework\MockObject\MockObject;

class ApplePayRequestTest extends UnitTestCase
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

        $this->moduleSettingsMock = $this->createMock(ModuleSettings::class);

        $mockPurchaseUnitsFactory = new PayPalPurchaseUnitsFactory($this->moduleSettingsMock);

        $this->orderRequestFactory = $this->getMockBuilder(OrderRequestFactory::class)
            ->setConstructorArgs([$mockPurchaseUnitsFactory, $this->moduleSettingsMock])
            ->onlyMethods([
                'getServiceFromContainer',
                'getUserNameFromBasket',
                'getCountryFromBasket',
                'getVaultingService',
                'getAmount',
                'getPurchaseUnits'
            ])
            ->getMock();

        $this->moduleSettingsMock = $this->createMock(ModuleSettings::class);


        $this->orderRequestFactory->method('getPurchaseUnits')
            ->willReturn([
                [
                    'breakdown' => [
                        'item_total' => [
                            'currency_code' => 'EUR',
                            'value' => '100.00'
                        ],
                        'tax_total' => [
                            'currency_code' => 'EUR',
                        ]
                    ]
                ]
            ]);


        $this->vaultingServiceMock = $this->createMock(VaultingService::class);
        $this->basketMock = $this->createMock(Basket::class);
        $this->userMock = oxNew(User::class);
        $this->userMock->assign([
            'oxcountryid' => 'a7c40f631fc920687.20179984'
        ]);
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
        $this->orderRequestFactory->method('getServiceFromContainer')
            ->with(ModuleSettings::class)
            ->willReturn($this->moduleSettingsMock);
        $this->countryMock->method('getFieldData')
            ->with('oxisoalpha2')
            ->willReturn('DE');

        Registry::set('oxsession', $this->sessionMock);
        Registry::set('oxconfig', $this->configMock);
    }

    public function testExperienceContextForCardPaymentWithVaulting(): void
    {
        $returnUrl = 'https://example.com/return';
        $cancelUrl = 'https://example.com/cancel';
        $userAction = OrderRequestFactory::USER_ACTION_PAY_NOW;

        $this->setupMocksForVaultedPayment(PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID);
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

        $paymentSourceArray = is_object($request->payment_source) ? $request->payment_source->jsonSerialize() : $request->payment_source;
        $cardSource = $paymentSourceArray['card'] ?? null;

        $this->assertNotNull($cardSource, 'Card payment source should be set');

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

        $paymentSourceArray = is_object($request->payment_source) ? $request->payment_source->jsonSerialize() : $request->payment_source;
        $paypalSource = $paymentSourceArray['paypal'] ?? null;
        $this->assertNotNull($paypalSource, 'PayPal payment source should be set for vaulting');

        if (isset($paypalSource->experience_context)) {
            $experienceContext = $paypalSource->experience_context;
            $this->assertIsArray($experienceContext);
        }
    }

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

        $paymentSourceArray = is_object($request->payment_source) ? $request->payment_source->jsonSerialize() : $request->payment_source;
        $applePaySource = $paymentSourceArray['apple_pay'] ?? null;
        $this->assertNotNull($applePaySource, 'Apple Pay payment source should be set');

        if (isset($applePaySource['experience_context'])) {
            $experienceContext = $applePaySource['experience_context'];
            $this->assertIsArray($experienceContext);
            $this->assertArrayHasKey('user_action', $experienceContext);
            $this->assertEquals($userAction, $experienceContext['user_action']);
            if (isset($experienceContext['return_url'])) {
                $this->assertEquals($returnUrl, $experienceContext['return_url']);
            }
            if (isset($experienceContext['cancel_url'])) {
                $this->assertEquals($cancelUrl, $experienceContext['cancel_url']);
            }
            if (isset($experienceContext['shipping_preference'])) {
                $this->assertContains($experienceContext['shipping_preference'], ['SET_PROVIDED_ADDRESS', 'SET_PROVIDED_ADDRESS', 'NO_SHIPPING']);
            }
            if (isset($experienceContext['brand_name'])) {
                $this->assertIsString($experienceContext['brand_name']);
                $this->assertNotEmpty($experienceContext['brand_name']);
            }
            if (isset($experienceContext['locale'])) {
                $this->assertMatchesRegularExpression(
                    '/^[a-z]{2}-[A-Z]{2}$/',
                    $experienceContext['locale'],
                    'Locale should be in format "xx-XX"'
                );
            }
        }
    }

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

        $paymentSourceArray = is_object($request->payment_source) ? $request->payment_source->jsonSerialize() : $request->payment_source;
        $applePaySource = $paymentSourceArray->apple_pay ?? null;
        $this->assertNotNull($applePaySource, 'Apple Pay payment source should be set for guest user');

        if (isset($applePaySource->experience_context)) {
            $experienceContext = $applePaySource->experience_context;
            $this->assertIsArray($experienceContext);
            if (isset($experienceContext['shipping_preference'])) {
                $this->assertEquals('SET_PROVIDED_ADDRESS', $experienceContext['shipping_preference']);
            }
            if (isset($experienceContext['user_action'])) {
                $this->assertEquals($userAction, $experienceContext['user_action']);
            }
        }
    }

    public function testApplePayPaymentSourceStructure(): void
    {
        $this->setupMocksForLoggedInPayment(PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID);
        $this->mockBasketAndAmountFactory();

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_PAY_NOW,
            null,
            null,
            null,
            null,
            'https://example.com/return',
            'https://example.com/cancel'
        );

        $paymentSourceArray = $request->payment_source;
        $applePaySource = $paymentSourceArray['apple_pay'] ?? null;
        $this->assertNotNull($applePaySource, 'Apple Pay payment source should be set');

        $this->assertIsArray($applePaySource);
        $this->assertArrayHasKey('name', $applePaySource);
        $this->assertEquals('John Doe', $applePaySource['name']);

        /*** TODO:// prove this works or a bug in the test
        * $this->assertArrayHasKey('country_code', $applePaySource);
        * $this->assertEquals('DE', $applePaySource['country_code']);
        * */

        if (isset($applePaySource['attributes'])) {
            $this->assertIsArray($applePaySource['attributes']);
            if (isset($applePaySource['attributes']['verification'])) {
                $this->assertArrayHasKey('method', $applePaySource['attributes']['verification']);
            }
        }
    }

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

        $this->assertInstanceOf(OrderRequest::class, $request);
        $this->assertNotNull($request->payment_source, 'Payment source should not be null');

        $paymentSourceArray = is_object($request->payment_source) ? $request->payment_source->jsonSerialize() : $request->payment_source;
        $applePaySource = $paymentSourceArray['apple_pay'] ?? null;
        $this->assertNotNull($applePaySource, 'Apple Pay payment source should be set');

        if (isset($applePaySource['experience_context'])) {
            $experienceContext = $applePaySource['experience_context'];
            $this->assertIsArray($experienceContext, 'Experience context should be an array');
            $this->assertArrayHasKey('return_url', $experienceContext, 'return_url should exist in Apple Pay experience_context');
            $this->assertArrayHasKey('cancel_url', $experienceContext, 'cancel_url should exist in Apple Pay experience_context');
            $this->assertEquals($returnUrl, $experienceContext['return_url'], 'Apple Pay return_url should match');
            $this->assertEquals($cancelUrl, $experienceContext['cancel_url'], 'Apple Pay cancel_url should match');
        }
    }

    public function testOrderRequestStructureForDifferentPaymentTypes(): void
    {
        $returnUrl = 'https://shop.com/success';
        $cancelUrl = 'https://shop.com/cancel';

        $paymentTypes = [
            PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID => 'apple_pay'
        ];

        foreach ($paymentTypes as $paymentId => $expectedSource) {
            $this->setupMocksForLoggedInPayment($paymentId);
            $request = $this->orderRequestFactory->getRequest(
                $this->basketMock,
                OrderRequest::INTENT_CAPTURE,
                OrderRequestFactory::USER_ACTION_PAY_NOW,
                null,
                null,
                null,
                null,
                $returnUrl,
                $cancelUrl
            );

            $paymentSourceArray = is_object($request->payment_source) ? $request->payment_source->jsonSerialize() : $request->payment_source;
            $this->assertArrayHasKey(
                $expectedSource,
                $paymentSourceArray,
                "Payment source should contain {$expectedSource} for payment type: {$paymentId}"
            );

            $sourceData = $paymentSourceArray[$expectedSource];
            if (isset($sourceData['experience_context'])) {
                $experienceContext = $sourceData['experience_context'];
                if (isset($experienceContext['return_url'])) {
                    $this->assertEquals($returnUrl, $experienceContext['return_url']);
                }
                if (isset($experienceContext['cancel_url'])) {
                    $this->assertEquals($cancelUrl, $experienceContext['cancel_url']);
                }
            }
        }
    }

    /**
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

        $paymentSourceArray = is_object($request->payment_source) ? $request->payment_source->jsonSerialize() : $request->payment_source;
        $this->assertArrayHasKey(
            $expectedPaymentSource,
            $paymentSourceArray,
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
                $this->assertArrayHasKey('return_url', $experienceContext, "return_url should exist in experience_context for {$paymentId}");
                $this->assertArrayHasKey('cancel_url', $experienceContext, "cancel_url should exist in experience_context for {$paymentId}");
                $this->assertEquals($returnUrl, $experienceContext['return_url'], "return_url should match for {$paymentId}");
                $this->assertEquals($cancelUrl, $experienceContext['cancel_url'], "cancel_url should match for {$paymentId}");
            } else {
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

    public function paymentTypesDataProvider(): array
    {
        return [
            'Apple Pay' => [
                PayPalDefinitions::APPLEPAY_PAYPAL_PAYMENT_ID,
                'apple_pay',
                true,
                true
            ],
            'Google Pay' => [
                PayPalDefinitions::GOOGLEPAY_PAYPAL_PAYMENT_ID,
                'google_pay',
                true,
                true
            ],
            'ACDC (Card)' => [
                PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'card',
                true,
                true
            ],
            'SEPA' => [
                PayPalDefinitions::SEPA_PAYPAL_PAYMENT_ID,
                'paypal',
                true,
                true
            ],
        ];
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
            ->willReturn(null);
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
            ->willReturn($userMock);
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
            ->willReturn(true);
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
}
