<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Service;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Service\SCAValidator;
use OxidSolutionCatalysts\PayPal\Exception\CardValidation as CardValidationException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse;

class CardValidationTest extends UnitTestCase
{
    private function createValidator(): SCAValidator
    {
        $moduleSettings = $this->createMock(ModuleSettings::class);
        $moduleSettings->method('alwaysIgnoreSCAResult')->willReturn(false);
        $moduleSettings->method('getPayPalSCAContingency')->willReturn(Constants::PAYPAL_SCA_WHEN_REQUIRED);
        return new SCAValidator($moduleSettings);
    }

    /**
     * Helper method to create a PayPalApiOrder with a configured PaymentSourceResponse and CardResponse.
     *
     * The $options array may contain:
     * - 'card' (bool): if false, no card will be set on the PaymentSourceResponse.
     * - 'last_digits': the card’s last digits.
     * - 'brand': the card brand (default "VISA").
     * - 'card_type': the card type (default "CREDIT").
     * - 'include_authentication' (bool): if false, card->authentication_result is left null.
     * - 'liability_shift': value to assign to the authentication result’s liability_shift.
     * - 'three_d_secure': an array with keys 'authentication_status' and 'enrollment_status' to create a 3DS response;
     *      if set to null, no 3DS data is provided.
     *
     * @param array $options
     * @return PayPalApiOrder
     */
    private function createOrderFromOptions(array $options): PayPalApiOrder
    {
        $order = new PayPalApiOrder();
        $paymentSource = new PaymentSourceResponse();

        if (isset($options['card']) && $options['card'] === false) {
            $paymentSource->card = null;
        } else {
            $card = new CardResponse();
            $card->last_digits = $options['last_digits'] ?? '0000';
            $card->brand = $options['brand'] ?? 'VISA';
            $card->type = $options['card_type'] ?? 'CREDIT';

            if (isset($options['include_authentication']) && $options['include_authentication'] === false) {
                $card->authentication_result = null;
            } else {
                $auth = new AuthenticationResponse();
                $auth->liability_shift = $options['liability_shift'] ?? 'NO';

                if (array_key_exists('three_d_secure', $options) && $options['three_d_secure'] !== null) {
                    $threeDS = new ThreeDSecureAuthenticationResponse();
                    $threeDS->authentication_status = $options['three_d_secure']['authentication_status'] ?? null;
                    $threeDS->enrollment_status = $options['three_d_secure']['enrollment_status'] ?? null;
                    $auth->three_d_secure = $threeDS;
                } else {
                    $auth->three_d_secure = null;
                }
                $card->authentication_result = $auth;
            }
            $paymentSource->card = $card;
        }
        $order->payment_source = $paymentSource;
        return $order;
    }


    public function testMissingPaymentSource(): void
    {
        $this->markTestSkipped("Requires database tables in testing environment; skipping.");
        $validator = $this->createValidator();

        $this->expectException(CardValidationException::class);
        $this->expectExceptionMessage(CardValidationException::byMissingPaymentSource()->getMessage());

        $order = new PayPalApiOrder();
        $validator->getCardAuthenticationResponse($order);
    }

    public function testNonCardPaymentSource(): void
    {
        $validator = $this->createValidator();

        $this->expectException(CardValidationException::class);
        $this->expectExceptionMessage(CardValidationException::byPaymentSource()->getMessage());

        // Create an order that has a PaymentSourceResponse but no card.
        $order = $this->createOrderFromOptions(['card' => false]);
        $validator->getCardAuthenticationResponse($order);
    }

    public function testMissingCardAutentication(): void
    {
        $validator = $this->createValidator();

        // Create an order with a card, but without any authentication_result.
        $order = $this->createOrderFromOptions([
            'last_digits'          => '9760',
            'include_authentication' => false
        ]);
        $this->assertNull($validator->getCardAuthenticationResponse($order));
    }

    public function testAuthenticationResultSuccess()
    {
        $validator = $this->createValidator();

        // Create an order with a successful authentication result.
        $order = $this->createOrderFromOptions([
            'last_digits'      => '7704',
            'liability_shift'  => 'POSSIBLE',
            'three_d_secure'   => [
                'authentication_status' => 'Y',
                'enrollment_status'     => 'Y'
            ]
        ]);

        $validationResult = $validator->getCardAuthenticationResponse($order);
        $this->assertSame(SCAValidator::LIABILITY_SHIFT_POSSIBLE, $validationResult->liability_shift);
        $this->assertSame(SCAValidator::AUTH_STATUS_SUCCESS, $validationResult->three_d_secure->authentication_status);
        $this->assertSame(SCAValidator::ENROLLMENT_STATUS_YES, $validationResult->three_d_secure->enrollment_status);
    }

    public function testIsCardSafeToUseFail()
    {
        $validator = $this->createValidator();

        // Enrolled card (Y) with failed authentication (N) and NO liability shift should not be allowed
        $order = $this->createOrderFromOptions([
            'last_digits'      => '1234',
            'liability_shift'  => 'NO',
            'three_d_secure'   => [
                'authentication_status' => 'N',
                'enrollment_status'     => 'Y'
            ]
        ]);

        $this->assertFalse($validator->isCardUsableForPayment($order), 'Card should not be usable for payment.');
    }


    /**
     * @dataProvider cardSafetyProvider
     */
    public function testIsCardSafeToUse(array $options, string $assertMethod)
    {
        $validator = $this->createValidator();
        $order = $this->createOrderFromOptions($options);
        $this->$assertMethod($validator->isCardUsableForPayment($order));
    }

    public function cardSafetyProvider(): array
    {
        return [
            'timeout' => [
                ['last_digits' => '7210', 'liability_shift' => 'NO', 'three_d_secure' => null],
                'assertTrue'
            ],

            'secured' => [
                [
                    'last_digits' => '1234',
                    'liability_shift' => 'YES',
                    'three_d_secure' => ['authentication_status' => 'Y', 'enrollment_status' => 'Y']
                ],
                'assertTrue'
            ],

            'attempted' => [
                [
                    'last_digits' => '1111',
                    'liability_shift' => 'POSSIBLE',
                    'three_d_secure' => ['authentication_status' => 'A', 'enrollment_status' => 'Y']
                ],
                'assertTrue'
            ]
        ];
    }

    public function testGeneratedSerializedOrder(): void
    {
        // Create an order with success parameters.
        $order = $this->createOrderFromOptions([
            'last_digits'     => '7704',
            'liability_shift' => 'POSSIBLE',
            'three_d_secure'  => [
                'authentication_status' => 'Y',
                'enrollment_status'     => 'Y'
            ]
        ]);
        $serialized = serialize($order);
        $this->assertNotEmpty($serialized, 'Generated serialized order should not be empty.');

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(
            PayPalApiOrder::class,
            $unserialized,
            'Unserialized object should be an instance of PayPalApiOrder.'
        );
    }

    public function testPaymentSourceResponseSerialization(): void
    {
        $paymentSourceResponse = new PaymentSourceResponse();
        $cardResponse = new CardResponse();
        $cardResponse->id              = 123;
        $cardResponse->name            = 'Test Card';
        $cardResponse->billing_address = '123 Test St';
        $cardResponse->last_n_chars    = '****';
        $cardResponse->last_digits     = '1234';
        $cardResponse->brand           = 'VISA';
        $cardResponse->type            = 'CREDIT';
        $cardResponse->issuer          = 'Test Issuer';
        $cardResponse->bin             = '123456';
        $cardResponse->authentication_result = 'SUCCESS';
        $cardResponse->attributes      = ['attr1' => 'value1'];

        $paymentSourceResponse->card   = $cardResponse;
        $paymentSourceResponse->paypal = 'paypal data';
        $paymentSourceResponse->wallet = 'wallet data';

        $serialized   = serialize($paymentSourceResponse);
        $deserialized = unserialize($serialized);

        $this->assertEquals(
            $paymentSourceResponse,
            $deserialized,
            'Deserialized PaymentSourceResponse must be identical to the original'
        );
    }
}
