<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use OxidSolutionCatalysts\PayPal\Service\SCAValidator;
use OxidSolutionCatalysts\PayPal\Exception\CardValidation as CardValidationException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Giropay;

class CardValidationTest extends TestCase
{
    private static string $missingCardAuthentication;
    private static string $nonCardPaymentSource;
    private static string $standardCard3D;
    private static string $success3DCard;
    private static string $failedSignature;
    private static string $failedAuthentication;
    private static string $noPrompt;
    private static string $timeout;
    private static string $notEnrolled;
    private static string $systemNotAvailable;
    private static string $merchantNotActive;
    private static string $failedSignature3DS1;
    private static string $cmpiLookupError;
    private static string $cmpiAuthError;
    private static string $unavailableAuth;
    private static string $bypassedAuth;

    protected function setUp(): void
    {
        $this->initSerializedVariables();
    }

    private static function initSerializedVariables(): void
    {
        self::$missingCardAuthentication  = self::getSerializedMissingCardAuthObject();
        self::$nonCardPaymentSource       = self::getSerializedNonCardPaymentSourceObject();
        self::$standardCard3D             = self::getSerializedStandardCard3DObject();
        self::$success3DCard              = self::getSerializedSuccess3DCardObject();
        self::$failedSignature            = self::getSerializedFailedSignatureObject();
        self::$failedAuthentication       = self::getSerializedFailedAuthenticationObject();
        self::$noPrompt                   = self::getSerializedNoPromptObject();
        self::$timeout                    = self::getSerializedTimeoutObject();
        self::$notEnrolled                = self::getSerializedNotEnrolledObject();
        self::$systemNotAvailable         = self::getSerializedSystemNotAvailableObject();
        self::$merchantNotActive          = self::getSerializedMerchantNotActiveObject();
        self::$failedSignature3DS1        = self::getSerializedFailedSignature3DS1Object();
        self::$cmpiLookupError            = self::getSerializedCmpiLookupErrorObject();
        self::$cmpiAuthError              = self::getSerializedCmpiAuthErrorObject();
        self::$unavailableAuth            = self::getSerializedUnavailableAuthObject();
        self::$bypassedAuth               = self::getSerializedBypassedAuthObject();
    }

    // Each of the following private methods builds a PayPalApiOrder with the desired properties and then serializes it.

    private static function getSerializedMissingCardAuthObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '9760';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';
        // authentication_result remains null

        $paymentSource->card = $card;
        return serialize($order);
    }

    private static function getSerializedNonCardPaymentSourceObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        // For non-card payments, set card to null...
        $paymentSource->card = null;
        // ...but provide a Giropay instance (as in your original string)
        $giropay = new Giropay();
        $giropay->name = 'Marc Muster';
        $giropay->country_code = 'DE';
        $giropay->bic = null;
        $paymentSource->giropay = $giropay;

        return serialize($order);
    }

    private static function getSerializedStandardCard3DObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '9760';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = 'NO';
        $threeDS = new ThreeDSecureAuthenticationResponse();
        $threeDS->authentication_status = null;
        $threeDS->enrollment_status = 'U';
        $authResponse->three_d_secure = $threeDS;
        $card->authentication_result = $authResponse;

        $paymentSource->card = $card;
        return serialize($order);
    }

    private static function getSerializedSuccess3DCardObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '7704';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = 'POSSIBLE';
        $threeDS = new ThreeDSecureAuthenticationResponse();
        $threeDS->authentication_status = 'Y';
        $threeDS->enrollment_status = 'Y';
        $authResponse->three_d_secure = $threeDS;
        $card->authentication_result = $authResponse;

        $paymentSource->card = $card;
        return serialize($order);
    }

    private static function getSerializedFailedSignatureObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '4992';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = 'UNKNOWN';
        $threeDS = new ThreeDSecureAuthenticationResponse();
        $threeDS->authentication_status = 'U';
        $threeDS->enrollment_status = 'Y';
        $authResponse->three_d_secure = $threeDS;
        $card->authentication_result = $authResponse;

        $paymentSource->card = $card;
        return serialize($order);
    }

    private static function getSerializedFailedAuthenticationObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '2421';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = 'NO';
        $threeDS = new ThreeDSecureAuthenticationResponse();
        $threeDS->authentication_status = 'N';
        $threeDS->enrollment_status = 'Y';
        $authResponse->three_d_secure = $threeDS;
        $card->authentication_result = $authResponse;

        $paymentSource->card = $card;
        return serialize($order);
    }

    private static function getSerializedNoPromptObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '5422';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = 'POSSIBLE';
        $threeDS = new ThreeDSecureAuthenticationResponse();
        $threeDS->authentication_status = 'A';
        $threeDS->enrollment_status = 'Y';
        $authResponse->three_d_secure = $threeDS;
        $card->authentication_result = $authResponse;

        $paymentSource->card = $card;
        return serialize($order);
    }

    private static function getSerializedTimeoutObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '7210';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = 'NO';
        // Simulate a timeout by leaving three_d_secure as null
        $authResponse->three_d_secure = null;
        $card->authentication_result = $authResponse;

        $paymentSource->card = $card;
        return serialize($order);
    }

    private static function getSerializedNotEnrolledObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '8803';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = 'NO';
        $threeDS = new ThreeDSecureAuthenticationResponse();
        $threeDS->authentication_status = null;
        $threeDS->enrollment_status = 'U';
        $authResponse->three_d_secure = $threeDS;
        $card->authentication_result = $authResponse;

        $paymentSource->card = $card;
        return serialize($order);
    }

    private static function getSerializedSystemNotAvailableObject(): string
    {
        // In this simulation, system not available mirrors the not-enrolled state.
        return self::getSerializedNotEnrolledObject();
    }

    private static function getSerializedMerchantNotActiveObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '6405';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = 'NO';
        // No three_d_secure information provided
        $authResponse->three_d_secure = null;
        $card->authentication_result = $authResponse;

        $paymentSource->card = $card;
        return serialize($order);
    }

    private static function getSerializedFailedSignature3DS1Object(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '0010';
        $card->brand = 'VISA';
        $card->type = 'UNKNOWN';

        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = 'NO';
        // No 3DS data provided
        $authResponse->three_d_secure = null;
        $card->authentication_result = $authResponse;

        $paymentSource->card = $card;
        return serialize($order);
    }

    private static function getSerializedCmpiLookupErrorObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '3346';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = 'NO';
        $authResponse->three_d_secure = null;
        $card->authentication_result = $authResponse;

        $paymentSource->card = $card;
        return serialize($order);
    }

    private static function getSerializedCmpiAuthErrorObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '4542';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = 'NO';
        $threeDS = new ThreeDSecureAuthenticationResponse();
        $threeDS->authentication_status = null;
        $threeDS->enrollment_status = 'Y';
        $authResponse->three_d_secure = $threeDS;
        $card->authentication_result = $authResponse;

        $paymentSource->card = $card;
        return serialize($order);
    }

    private static function getSerializedUnavailableAuthObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '8815';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = 'UNKNOWN';
        $threeDS = new ThreeDSecureAuthenticationResponse();
        $threeDS->authentication_status = 'U';
        $threeDS->enrollment_status = 'Y';
        $authResponse->three_d_secure = $threeDS;
        $card->authentication_result = $authResponse;

        $paymentSource->card = $card;
        return serialize($order);
    }

    private static function getSerializedBypassedAuthObject(): string
    {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();

        $card = new CardResponse();
        $card->last_digits = '8584';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = 'NO';
        $threeDS = new ThreeDSecureAuthenticationResponse();
        $threeDS->authentication_status = null;
        $threeDS->enrollment_status = 'B';
        $authResponse->three_d_secure = $threeDS;
        $card->authentication_result = $authResponse;

        $paymentSource->card = $card;
        return serialize($order);
    }

    // ----------------------------------------------------------------
    // Tests using the serialized objects
    // ----------------------------------------------------------------

    public function testMissingPaymentSource(): void
    {
        $validator = new SCAValidator();

        $this->expectException(CardValidationException::class);
        $this->expectExceptionMessage(CardValidationException::byMissingPaymentSource()->getMessage());

        $validator->getCardAuthenticationResult(new PayPalApiOrder());
    }

    public function testNonCardPaymentSource(): void
    {
        $validator = new SCAValidator();

        $this->expectException(CardValidationException::class);
        $this->expectExceptionMessage(CardValidationException::byPaymentSource()->getMessage());

        $validator->getCardAuthenticationResult(unserialize($this->nonCardPaymentSource));
    }

    public function testMissingCardAutentication(): void
    {
        $validator = new SCAValidator();

        $order = unserialize($this->missingCardAuthentication);
        $this->assertNull($validator->getCardAuthenticationResult($order));
    }

    public function testAuthenticationResultSuccess()
    {
        $validator = new SCAValidator();

        $order = unserialize($this->success3DCard);
        $validationResult = $validator->getCardAuthenticationResult($order);
        $this->assertSame(SCAValidator::LIABILITY_SHIFT_POSSIBLE, $validationResult->liability_shift);
        $this->assertSame(SCAValidator::AUTH_STATUS_SUCCESS, $validationResult->three_d_secure->authentication_status);
        $this->assertSame(SCAValidator::ENROLLMENT_STATUS_YES, $validationResult->three_d_secure->enrollment_status);
    }

    /**
     * @dataProvider providerPayPalApiOrderResults
     */
    public function testIsCardSafeToUse(string $serializedOrder, string $assertMethod)
    {
        $validator = new SCAValidator();
        $this->{$assertMethod}($validator->isCardUsableForPayment(unserialize($serializedOrder)));
    }

    public function providerPayPalApiOrderResults(): array
    {
        self::initSerializedVariables();
        return [
            'success'            => ['success' => self::$success3DCard,       'method' => 'assertTrue'],
            'standardcard'       => ['success' => self::$standardCard3D,       'method' => 'assertTrue'],
            'failesignature'     => ['success' => self::$failedSignature,      'method' => 'assertFalse'],
            'failedauth'         => ['success' => self::$failedAuthentication, 'method' => 'assertFalse'],
            'no_credemtial_prompt' => ['success' => self::$noPrompt,            'method' => 'assertTrue'],
            'timeout'            => ['success' => self::$timeout,             'method' => 'assertFalse'],
            'not_enrolled'       => ['success' => self::$notEnrolled,         'method' => 'assertTrue'],
            'system_not_available' => ['success' => self::$systemNotAvailable,  'method' => 'assertTrue'],
            'merchant_not_active' => ['success' => self::$merchantNotActive,   'method' => 'assertFalse'],
            'failed_3Ds1'        => ['success' => self::$failedSignature3DS1,   'method' => 'assertFalse'],
            'cmpiLookupError'    => ['success' => self::$cmpiLookupError,       'method' => 'assertFalse'],
            'cmpiAuthError'      => ['success' => self::$cmpiAuthError,         'method' => 'assertFalse'],
            'unavailableAuth'    => ['success' => self::$unavailableAuth,       'method' => 'assertFalse'],
            'bypassedAuth'       => ['success' => self::$bypassedAuth,          'method' => 'assertTrue'],
        ];
    }

    public function testIsCardSafeToUseFail()
    {
        $validator = new SCAValidator();
        $this->assertFalse($validator->isCardUsableForPayment(unserialize($this->missingCardAuthentication)));
    }
}
