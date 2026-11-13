<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
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
    private string $missingCardAuthentication;
    private string $nonCardPaymentSource;
    private string $standardCard3D;
    private string $success3DCard;
    private string $failedSignature;
    private string $failedAuthentication;
    private string $noPrompt;
    private string $timeout;
    private string $notEnrolled;
    private string $systemNotAvailable;
    private string $merchantNotActive;
    private string $failedSignature3DS1;
    private string $cmpiLookupError;
    private string $cmpiAuthError;
    private string $unavailableAuth;
    private string $bypassedAuth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initSerializedVariables();
    }

    private function createValidator(): SCAValidator
    {
        return new SCAValidator($this->createMock(ModuleSettings::class));
    }

    private function initSerializedVariables(): void
    {
        $this->missingCardAuthentication = $this->getSerializedMissingCardAuthObject();
        $this->nonCardPaymentSource       = $this->getSerializedNonCardPaymentSourceObject();
        $this->standardCard3D             = $this->getSerializedStandardCard3DObject();
        $this->success3DCard              = $this->getSerializedSuccess3DCardObject();
        $this->failedSignature            = $this->getSerializedFailedSignatureObject();
        $this->failedAuthentication       = $this->getSerializedFailedAuthenticationObject();
        $this->noPrompt                   = $this->getSerializedNoPromptObject();
        $this->timeout                    = $this->getSerializedTimeoutObject();
        $this->notEnrolled                = $this->getSerializedNotEnrolledObject();
        $this->systemNotAvailable         = $this->getSerializedSystemNotAvailableObject();
        $this->merchantNotActive          = $this->getSerializedMerchantNotActiveObject();
        $this->failedSignature3DS1        = $this->getSerializedFailedSignature3DS1Object();
        $this->cmpiLookupError            = $this->getSerializedCmpiLookupErrorObject();
        $this->cmpiAuthError              = $this->getSerializedCmpiAuthErrorObject();
        $this->unavailableAuth            = $this->getSerializedUnavailableAuthObject();
        $this->bypassedAuth               = $this->getSerializedBypassedAuthObject();
    }

    // Each of the following private methods builds a PayPalApiOrder with the desired properties and then serializes it.

    private function getSerializedMissingCardAuthObject(): string
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

    private function getSerializedNonCardPaymentSourceObject(): string
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

    private function getSerializedStandardCard3DObject(): string
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

    private function getSerializedSuccess3DCardObject(): string
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

    private function getSerializedFailedSignatureObject(): string
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

    private function getSerializedFailedAuthenticationObject(): string
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

    private function getSerializedNoPromptObject(): string
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

    private function getSerializedTimeoutObject(): string
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

    private function getSerializedNotEnrolledObject(): string
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

    private function getSerializedSystemNotAvailableObject(): string
    {
        // In this simulation, system not available mirrors the not-enrolled state.
        return $this->getSerializedNotEnrolledObject();
    }

    private function getSerializedMerchantNotActiveObject(): string
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

    private function getSerializedFailedSignature3DS1Object(): string
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

    private function getSerializedCmpiLookupErrorObject(): string
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

    private function getSerializedCmpiAuthErrorObject(): string
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

    private function getSerializedUnavailableAuthObject(): string
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

    private function getSerializedBypassedAuthObject(): string
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
        $validator = $this->createValidator();

        $this->expectException(CardValidationException::class);
        $this->expectExceptionMessage(CardValidationException::byMissingPaymentSource()->getMessage());

        $validator->getCardAuthenticationResponse(new PayPalApiOrder());
    }

    public function testNonCardPaymentSource(): void
    {
        $validator = $this->createValidator();

        $this->expectException(CardValidationException::class);
        $this->expectExceptionMessage(CardValidationException::byPaymentSource()->getMessage());

        $validator->getCardAuthenticationResponse(unserialize($this->nonCardPaymentSource));
    }

    public function testMissingCardAutentication(): void
    {
        $validator = $this->createValidator();

        $order = unserialize($this->missingCardAuthentication);
        $this->assertNull($validator->getCardAuthenticationResponse($order));
    }

    public function testAuthenticationResultSuccess()
    {
        $validator = $this->createValidator();

        $order = unserialize($this->success3DCard);
        $validationResult = $validator->getCardAuthenticationResponse($order);
        $this->assertSame(SCAValidator::LIABILITY_SHIFT_POSSIBLE, $validationResult->liability_shift);
        $this->assertSame(SCAValidator::AUTH_STATUS_SUCCESS, $validationResult->three_d_secure->authentication_status);
        $this->assertSame(SCAValidator::ENROLLMENT_STATUS_YES, $validationResult->three_d_secure->enrollment_status);
    }

    /**
     * Decision matrix of SCAValidator::isCardUsableForPayment(), named after PayPal's 3D Secure
     * test cards. See https://developer.paypal.com/docs/checkout/advanced/customize/3d-secure/response-parameters/
     *
     * timeout, merchant_not_active, failed_3Ds1 and cmpiLookupError carry no 3D Secure data and
     * liability shift NO. PayPal's table has no row for this combination; the module continues
     * with authorization (no 3D Secure was performed, the merchant keeps the liability).
     *
     * @dataProvider providerPayPalApiOrderResults
     */
    public function testIsCardSafeToUse(string $serializedOrder, string $assertMethod)
    {
        $validator = $this->createValidator();

        $this->{$assertMethod}($validator->isCardUsableForPayment(unserialize($serializedOrder)));
    }

    public function providerPayPalApiOrderResults(): array
    {
        $this->initSerializedVariables();
        return [
            'success'            => ['success' => $this->success3DCard,       'method' => 'assertTrue'],
            'standardcard'       => ['success' => $this->standardCard3D,       'method' => 'assertTrue'],
            'failesignature'     => ['success' => $this->failedSignature,      'method' => 'assertFalse'],
            'failedauth'         => ['success' => $this->failedAuthentication, 'method' => 'assertFalse'],
            'no_credemtial_prompt' => ['success' => $this->noPrompt,            'method' => 'assertTrue'],
            'timeout'            => ['success' => $this->timeout,             'method' => 'assertTrue'],
            'not_enrolled'       => ['success' => $this->notEnrolled,         'method' => 'assertTrue'],
            'system_not_available' => ['success' => $this->systemNotAvailable,  'method' => 'assertTrue'],
            'merchant_not_active' => ['success' => $this->merchantNotActive,   'method' => 'assertTrue'],
            'failed_3Ds1'        => ['success' => $this->failedSignature3DS1,   'method' => 'assertTrue'],
            'cmpiLookupError'    => ['success' => $this->cmpiLookupError,       'method' => 'assertTrue'],
            'cmpiAuthError'      => ['success' => $this->cmpiAuthError,         'method' => 'assertFalse'],
            'unavailableAuth'    => ['success' => $this->unavailableAuth,       'method' => 'assertFalse'],
            'bypassedAuth'       => ['success' => $this->bypassedAuth,          'method' => 'assertTrue'],
            // PayPal: system unavailable - do not continue, ask the cardholder to retry
            'unknown_enrollment_liability_unknown' => [
                'success' => $this->buildSerializedCardOrder('U', null, 'UNKNOWN'),
                'method' => 'assertFalse'
            ],
            'no_3ds_data_liability_unknown' => [
                'success' => $this->buildSerializedCardOrder(null, null, 'UNKNOWN'),
                'method' => 'assertFalse'
            ],
            // PayPal: system unavailable with liability shift NO - continue
            'unknown_enrollment_liability_no' => [
                'success' => $this->buildSerializedCardOrder('U', null, 'NO'),
                'method' => 'assertTrue'
            ],
        ];
    }

    /**
     * Card order with an authentication result; null enrollment and authentication status
     * mean no 3D Secure data at all.
     */
    private function buildSerializedCardOrder(
        ?string $enrollmentStatus,
        ?string $authenticationStatus,
        string $liabilityShift
    ): string {
        $order = new PayPalApiOrder();
        $paymentSource = $order->initPaymentSource();
        $card = new CardResponse();
        $card->last_digits = '0000';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';
        $authResponse = new AuthenticationResponse();
        $authResponse->liability_shift = $liabilityShift;
        if ($enrollmentStatus !== null || $authenticationStatus !== null) {
            $threeDS = new ThreeDSecureAuthenticationResponse();
            $threeDS->enrollment_status = $enrollmentStatus;
            $threeDS->authentication_status = $authenticationStatus;
            $authResponse->three_d_secure = $threeDS;
        }
        $card->authentication_result = $authResponse;
        $paymentSource->card = $card;

        return serialize($order);
    }

    /**
     * Without any authentication result no 3D Secure was performed. PayPal's documentation does
     * not cover this case; the module continues with authorization (the merchant keeps the
     * liability), consistent with SCAValidator::verify3D() for "SCA when required".
     */
    public function testIsCardSafeToUseWithoutAuthenticationResult()
    {
        $validator = $this->createValidator();
        $this->assertTrue($validator->isCardUsableForPayment(unserialize($this->missingCardAuthentication)));
    }
}
