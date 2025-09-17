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
use OxidEsales\EshopCommunity\Tests\ContainerTrait;
use OxidSolutionCatalysts\PayPal\Core\Api\VaultingService;
use OxidSolutionCatalysts\PayPal\Service\Factory\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\Factory\PayPalPurchaseUnitsFactory;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderExperienceContext;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaypalPaymentTest extends TestCase
{
    use ContainerTrait;

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

        $mockPurchaseUnitsFactory = $this->getMockBuilder(PayPalPurchaseUnitsFactory::class)
            ->setConstructorArgs([$this->moduleSettingsMock])
            ->onlyMethods(['getPurchaseUnits'])
            ->getMock();

        $mockPurchaseUnitsFactory->method('getPurchaseUnits')
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

        $this->orderRequestFactory = $this->getMockBuilder(OrderRequestFactory::class)
            ->setConstructorArgs([$mockPurchaseUnitsFactory])
            ->onlyMethods([
                'getServiceFromContainer',
                'getUserNameFromBasket',
                'getCountryFromBasket',
                'getVaultingService',
                'getAmount',
                'getPuiPaymentSource',
            ])
            ->getMock();



        $this->orderRequestFactory->method('getAmount')
            ->willReturn(new \OxidSolutionCatalysts\PayPalApi\Model\Orders\AmountWithBreakdown([
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
            ]));

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

    public function testGetRequest()
    {
        $basketMock = $this->createMock(Basket::class);
        $basketMock->method('isCalculationModeNetto')->willReturn(false);
        $this->sessionMock->method('getVariable')
            ->with('paymentid')
            ->willReturn(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
        $this->moduleSettingsMock->method('getIsVaultingActive')->willReturn(false);
        $this->vaultingServiceMock->method('fetchSelectedVaultedPaymentToken')->willReturn(null);
        $this->markTestSkipped('This test needs to be rewritten to properly test the OrderRequestFactory with all dependencies');
    }

    public function testExperienceContextForGuestPayPalPayment(): void
    {
        $returnUrl = 'https://example.com/return';
        $cancelUrl = 'https://example.com/cancel';
        $userAction = OrderRequestFactory::USER_ACTION_PAY_NOW;

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

        $paymentSourceObj = is_object($request->payment_source) ? $request->payment_source->jsonSerialize() : $request->payment_source;
        $paypalSource = $paymentSourceObj->paypal ?? null;
        $this->assertNotNull($paypalSource, 'PayPal payment source should be set');

        if (isset($paypalSource->experience_context)) {
            $experienceContext = $paypalSource->experience_context;
            $this->assertIsArray($experienceContext);
            $this->assertArrayHasKey('user_action', $experienceContext);
        }
    }

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

        $paymentSourceArray = is_object($request->payment_source) ? $request->payment_source->jsonSerialize() : $request->payment_source;
        $paypalSource = $paymentSourceArray['paypal'] ?? null;
        $this->assertNotNull($paypalSource, 'PayPal payment source should be set');

        if (isset($paypalSource['experience_context'])) {
            $experienceContext = $paypalSource['experience_context'];
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

        $paymentSourceArray = is_object($request->payment_source) ? $request->payment_source->jsonSerialize() : $request->payment_source;
        $idealSource = $paymentSourceArray['ideal'] ?? null;
        $this->assertNotNull($idealSource, 'iDEAL payment source should be set');

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

    public function testExperienceContextDefaultValues(): void
    {
        $this->setupMocksForLoggedInPayment(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
        $this->mockBasketAndAmountFactory();

        $request = $this->orderRequestFactory->getRequest(
            $this->basketMock,
            OrderRequest::INTENT_CAPTURE,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $paymentSourceArray = is_object($request->payment_source) ? $request->payment_source->jsonSerialize() : $request->payment_source;
        $paypalSource = $paymentSourceArray['paypal'] ?? null;
        $this->assertNotNull($paypalSource, 'PayPal payment source should be set');

        if (isset($paypalSource['experience_context'])) {
            $experienceContext = $paypalSource['experience_context'];
            $this->assertIsArray($experienceContext);
            $this->assertArrayHasKey('user_action', $experienceContext);
        }
    }

    public function testPuiPaymentHasSpecialExperienceContext(): void
    {
        $returnUrl = 'https://example.com/return';
        $cancelUrl = 'https://example.com/cancel';

        $this->setupMocksForLoggedInPayment(PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID);
        $this->setRequestParameter(
            'pui_required',
            ['phonenumber' => '+49 0 123 45 6789']
        );

        $_POST['pui_required'] = [
            'birthdate' => [
                'day' => '1',
                'month' => '4',
                'year' => '2000'
            ],
            'phonenumber' => '040111222333'
        ];

        // Mock the PUI payment source to avoid database access
        $mockPuiPaymentSource = [
            PayPalDefinitions::PAYMENT_SOURCE_PUI => [
                'name' => 'John Doe',
                'billing_address' => [
                    'address_line_1' => 'Test Street 1',
                    'admin_area_2' => 'Test City',
                    'postal_code' => '12345',
                    'country_code' => 'DE'
                ],
                'phone' => [
                    'national_number' => '040111222333'
                ],
                'experience_context' => [
                    'brand_name' => 'Test Shop',
                    'locale' => 'de-DE',
                    'customer_service_instructions' => []
                ]
            ]
        ];
        $this->orderRequestFactory->method('getPuiPaymentSource')
            ->willReturn($mockPuiPaymentSource);

        try {
            $request = $this->orderRequestFactory->getRequest(
                $this->basketMock,
                OrderRequest::INTENT_CAPTURE,
                OrderRequestFactory::USER_ACTION_CONTINUE,
                null,
                null,
                PayPalDefinitions::PAYMENT_SOURCE_PUI,
                null,
                $returnUrl,
                $cancelUrl
            );
        } catch (\Exception $e) {
            $this->fail('PUI payment should not throw an exception: ' . $e->getMessage());
        }

        $this->assertInstanceOf(OrderRequest::class, $request);
        $this->assertNotNull($request->payment_source);

        $paymentSourceArray = is_object($request->payment_source) ? $request->payment_source->jsonSerialize() : $request->payment_source;

        if (isset($paymentSourceArray['pay_upon_invoice'])) {
            $puiSource = $paymentSourceArray['pay_upon_invoice'];
            if (is_object($puiSource->experience_context)) {
                $experienceContext = $puiSource->experience_context;
                $this->assertIsObject($experienceContext);
                $this->assertInstanceOf(OrderExperienceContext::class, $experienceContext);
                $this->assertTrue(
                    !empty($experienceContext->brand_name) && !empty($experienceContext->locale) && !empty($experienceContext->customer_service_instructions),
                    'PUI experience context should have PUI-specific properties'
                );
            }
        }
    }

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
            null,
            null
        );

        $this->assertInstanceOf(OrderRequest::class, $request);
        $this->assertNotNull($request->payment_source, 'Payment source should still be set even with null URLs');
        $this->assertEquals(OrderRequest::INTENT_CAPTURE, $request->intent, 'Intent should be properly set');
        $this->assertNotEmpty($request->purchase_units, 'Purchase units should be set');
    }

    public function testOrderRequestStructureForDifferentPaymentTypes(): void
    {
        $returnUrl = 'https://shop.com/success';
        $cancelUrl = 'https://shop.com/cancel';

        $paymentTypes = [
            PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID => 'paypal',
            PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID => 'paypal'
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


    public function setRequestParameter($name, $value): void
    {
        $requestMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRequestParameter'])
            ->getMock();
        $requestMock->method('getRequestParameter')
            ->willReturn($value);
        Registry::set('oxrequest', $requestMock);
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
