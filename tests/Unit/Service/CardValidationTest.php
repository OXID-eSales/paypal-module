<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Service;

use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Service\SCAValidator;
use OxidSolutionCatalysts\PayPal\Exception\CardValidation as CardValidationException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse;

class CardValidationTest extends UnitTestCase
{
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

        // Decide whether a card object should exist.
        if (isset($options['card']) && $options['card'] === false) {
            $paymentSource->card = null;
        } else {
            $card = new CardResponse();
            $card->last_digits = $options['last_digits'] ?? '0000';
            $card->brand       = $options['brand'] ?? 'VISA';
            $card->type        = $options['card_type'] ?? 'CREDIT';

            if (isset($options['include_authentication']) && $options['include_authentication'] === false) {
                $card->authentication_result = null;
            } else {
                $auth = new AuthenticationResponse();
                $auth->liability_shift = $options['liability_shift'] ?? 'NO';

                if (array_key_exists('three_d_secure', $options) && $options['three_d_secure'] !== null) {
                    $threeDS = new ThreeDSecureAuthenticationResponse();
                    $threeDS->authentication_status = $options['three_d_secure']['authentication_status'] ?? null;
                    $threeDS->enrollment_status     = $options['three_d_secure']['enrollment_status'] ?? null;
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
        $this->markTestSkipped("strange error  Table 'example.oxv_oxshops_en' doesn't exist in database.");
        $validator = new SCAValidator();

        $this->expectException(DatabaseErrorException::class);
        $this->expectException(CardValidationException::class);
        $this->expectExceptionMessage(CardValidationException::byMissingPaymentSource()->getMessage());

        $order = new PayPalApiOrder();
        $validator->getCardAuthenticationResult($order);
    }

    public function testNonCardPaymentSource(): void
    {
        $validator = new SCAValidator();

        $this->expectException(CardValidationException::class);
        $this->expectExceptionMessage(CardValidationException::byPaymentSource()->getMessage());

        // Create an order that has a PaymentSourceResponse but no card.
        $order = $this->createOrderFromOptions(['card' => false]);
        $validator->getCardAuthenticationResult($order);
    }

    public function testMissingCardAutentication(): void
    {
        $validator = new SCAValidator();

        // Create an order with a card, but without any authentication_result.
        $order = $this->createOrderFromOptions([
            'last_digits'          => '9760',
            'include_authentication' => false
        ]);
        $this->assertNull($validator->getCardAuthenticationResult($order));
    }

    public function testAuthenticationResultSuccess()
    {
        $validator = new SCAValidator();

        // Create an order with a successful authentication result.
        $order = $this->createOrderFromOptions([
            'last_digits'      => '7704',
            'liability_shift'  => 'POSSIBLE',
            'three_d_secure'   => [
                'authentication_status' => 'Y',
                'enrollment_status'     => 'Y'
            ]
        ]);

        $validationResult = $validator->getCardAuthenticationResult($order);
        $this->assertSame(SCAValidator::LIABILITY_SHIFT_POSSIBLE, $validationResult->liability_shift);
        $this->assertSame(SCAValidator::AUTH_STATUS_SUCCESS, $validationResult->three_d_secure->authentication_status);
        $this->assertSame(SCAValidator::ENROLLMENT_STATUS_YES, $validationResult->three_d_secure->enrollment_status);
    }

    public function testIsCardSafeToUseFail()
    {
        $validator = new SCAValidator();

        // Use an order without authentication_result.
        $order = $this->createOrderFromOptions([
            'last_digits'          => '9760',
            'include_authentication' => false
        ]);
        $this->assertFalse($validator->isCardUsableForPayment($order));
    }

    /**
     * @dataProvider providerPayPalApiOrderResults
     */
    public function testIsCardSafeToUse(array $options, string $assertMethod)
    {
        $validator = new SCAValidator();
        $order = $this->createOrderFromOptions($options);
        $this->$assertMethod($validator->isCardUsableForPayment($order));
    }

    public function providerPayPalApiOrderResults(): array
    {
        return [
            'success' => [
                'options' => [
                    'last_digits'     => '7704',
                    'liability_shift' => 'POSSIBLE',
                    'three_d_secure'  => [
                        'authentication_status' => 'Y',
                        'enrollment_status'     => 'Y'
                    ]
                ],
                'assertMethod' => 'assertTrue'
            ],
            'standardcard' => [
                'options' => [
                    'last_digits'     => '9760',
                    'liability_shift' => 'POSSIBLE',
                    'three_d_secure'  => [
                        'authentication_status' => 'Y',
                        'enrollment_status'     => 'Y'
                    ]
                ],
                'assertMethod' => 'assertTrue'
            ],
            'failesignature' => [
                'options' => [
                    'last_digits'     => '4992',
                    'liability_shift' => 'UNKNOWN',
                    'three_d_secure'  => [
                        'authentication_status' => 'U',
                        'enrollment_status'     => 'Y'
                    ]
                ],
                'assertMethod' => 'assertFalse'
            ],
            'failedauth' => [
                'options' => [
                    'last_digits'     => '2421',
                    'liability_shift' => 'NO',
                    'three_d_secure'  => [
                        'authentication_status' => 'N',
                        'enrollment_status'     => 'Y'
                    ]
                ],
                'assertMethod' => 'assertFalse'
            ],
            'no_credemtial_prompt' => [
                'options' => [
                    'last_digits'     => '5422',
                    'liability_shift' => 'POSSIBLE',
                    'three_d_secure'  => [
                        'authentication_status' => 'A',
                        'enrollment_status'     => 'Y'
                    ]
                ],
                'assertMethod' => 'assertTrue'
            ],
            'timeout' => [
                'options' => [
                    'last_digits'     => '7210',
                    'liability_shift' => 'NO',
                    'three_d_secure'  => null
                ],
                'assertMethod' => 'assertFalse'
            ],
            'not_enrolled' => [
                'options' => [
                    'last_digits'     => '8803',
                    'liability_shift' => 'NO',
                    'three_d_secure'  => [
                        'authentication_status' => null,
                        'enrollment_status'     => 'U'
                    ]
                ],
                'assertMethod' => 'assertTrue'
            ],
            'system_not_available' => [
                'options' => [
                    'last_digits'     => '8803',
                    'liability_shift' => 'NO',
                    'three_d_secure'  => [
                        'authentication_status' => null,
                        'enrollment_status'     => 'U'
                    ]
                ],
                'assertMethod' => 'assertTrue'
            ],
            'merchant_not_active' => [
                'options' => [
                    'last_digits'     => '6405',
                    'liability_shift' => 'NO',
                    'three_d_secure'  => null
                ],
                'assertMethod' => 'assertFalse'
            ],
            'failed_3Ds1' => [
                'options' => [
                    'last_digits'     => '0010',
                    'brand'           => 'VISA',
                    'card_type'       => 'UNKNOWN',
                    'liability_shift' => 'NO',
                    'three_d_secure'  => null
                ],
                'assertMethod' => 'assertFalse'
            ],
            'cmpiLookupError' => [
                'options' => [
                    'last_digits'     => '3346',
                    'liability_shift' => 'NO',
                    'three_d_secure'  => null
                ],
                'assertMethod' => 'assertFalse'
            ],
            'cmpiAuthError' => [
                'options' => [
                    'last_digits'     => '4542',
                    'liability_shift' => 'NO',
                    'three_d_secure'  => [
                        'authentication_status' => null,
                        'enrollment_status'     => 'Y'
                    ]
                ],
                'assertMethod' => 'assertFalse'
            ],
            'unavailableAuth' => [
                'options' => [
                    'last_digits'     => '8815',
                    'liability_shift' => 'UNKNOWN',
                    'three_d_secure'  => [
                        'authentication_status' => 'U',
                        'enrollment_status'     => 'Y'
                    ]
                ],
                'assertMethod' => 'assertFalse'
            ],
            'bypassedAuth' => [
                'options' => [
                    'last_digits'     => '8584',
                    'liability_shift' => 'NO',
                    'three_d_secure'  => [
                        'authentication_status' => null,
                        'enrollment_status'     => 'B'
                    ]
                ],
                'assertMethod' => 'assertTrue'
            ],
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
