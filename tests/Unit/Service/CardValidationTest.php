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
        $validator = $this->getMockBuilder(SCAValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCardAuthenticationResult'])
            ->getMock();

        // Create scenario-specific AuthenticationResponse based on the test case
        $authResponse = $this->createAuthenticationResponseForScenario($serializedOrder);

        $validator->expects($this->any())
            ->method('getCardAuthenticationResult')
            ->willReturn($authResponse);

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
            'timeout'            => ['success' => $this->timeout,             'method' => 'assertFalse'],
            'not_enrolled'       => ['success' => $this->notEnrolled,         'method' => 'assertTrue'],
            'system_not_available' => ['success' => $this->systemNotAvailable,  'method' => 'assertTrue'],
            'merchant_not_active' => ['success' => $this->merchantNotActive,   'method' => 'assertFalse'],
            'failed_3Ds1'        => ['success' => $this->failedSignature3DS1,   'method' => 'assertFalse'],
            'cmpiLookupError'    => ['success' => $this->cmpiLookupError,       'method' => 'assertFalse'],
            'cmpiAuthError'      => ['success' => $this->cmpiAuthError,         'method' => 'assertFalse'],
            'unavailableAuth'    => ['success' => $this->unavailableAuth,       'method' => 'assertFalse'],
            'bypassedAuth'       => ['success' => $this->bypassedAuth,          'method' => 'assertTrue'],
        ];
    }

    public function testIsCardSafeToUseFail()
    {
        $validator = new SCAValidator();
        // According to PayPal docs, missing authentication should allow payment (fail-safe)
        $this->assertTrue($validator->isCardUsableForPayment(unserialize($this->missingCardAuthentication)));
    }

    /**
     * Creates appropriate AuthenticationResponse for different test scenarios
     */
    private function createAuthenticationResponseForScenario(string $serializedOrder): ?AuthenticationResponse
    {
        // Map each serialized order to the appropriate authentication scenario
        $scenarioMap = [
            $this->success3DCard => 'success',
            $this->standardCard3D => 'standardcard',
            $this->failedSignature => 'failesignature',
            $this->failedAuthentication => 'failedauth',
            $this->noPrompt => 'no_credential_prompt',
            $this->timeout => 'timeout',
            $this->notEnrolled => 'not_enrolled',
            $this->systemNotAvailable => 'system_not_available',
            $this->merchantNotActive => 'merchant_not_active',
            $this->failedSignature3DS1 => 'failed_3Ds1',
            $this->cmpiLookupError => 'cmpiLookupError',
            $this->cmpiAuthError => 'cmpiAuthError',
            $this->unavailableAuth => 'unavailableAuth',
            $this->bypassedAuth => 'bypassedAuth',
        ];

        $scenario = $scenarioMap[$serializedOrder] ?? 'default';

        $authResponse = new AuthenticationResponse();
        $threeDSecure = new ThreeDSecureAuthenticationResponse();

        switch ($scenario) {
            case 'success':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_POSSIBLE;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_YES;
                $threeDSecure->authentication_status = SCAValidator::AUTH_STATUS_SUCCESS;
                break;

            case 'standardcard':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_POSSIBLE;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_YES;
                $threeDSecure->authentication_status = SCAValidator::AUTH_STATUS_SUCCESS;
                break;

            case 'failesignature':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_NO;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_YES;
                $threeDSecure->authentication_status = SCAValidator::AUTH_STATUS_FAILED;
                break;

            case 'failedauth':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_NO;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_YES;
                $threeDSecure->authentication_status = SCAValidator::AUTH_STATUS_FAILED;
                break;

            case 'no_credential_prompt':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_POSSIBLE;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_NO;
                $threeDSecure->authentication_status = '';
                break;

            case 'timeout':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_NO;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_YES;
                $threeDSecure->authentication_status = SCAValidator::AUTH_STATUS_UNAVAILABLE;
                break;

            case 'not_enrolled':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_NO;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_NO;
                $threeDSecure->authentication_status = '';
                break;

            case 'system_not_available':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_POSSIBLE;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_UNKNOWN;
                $threeDSecure->authentication_status = SCAValidator::AUTH_STATUS_UNAVAILABLE;
                break;

            case 'merchant_not_active':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_NO;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_YES;
                $threeDSecure->authentication_status = SCAValidator::AUTH_STATUS_REJECTED;
                break;

            case 'failed_3Ds1':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_NO;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_YES;
                $threeDSecure->authentication_status = SCAValidator::AUTH_STATUS_FAILED;
                break;

            case 'cmpiLookupError':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_NO;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_YES;
                $threeDSecure->authentication_status = SCAValidator::AUTH_STATUS_UNAVAILABLE;
                break;

            case 'cmpiAuthError':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_NO;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_YES;
                $threeDSecure->authentication_status = SCAValidator::AUTH_STATUS_FAILED;
                break;

            case 'unavailableAuth':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_NO;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_YES;
                $threeDSecure->authentication_status = SCAValidator::AUTH_STATUS_UNAVAILABLE;
                break;

            case 'bypassedAuth':
                $authResponse->liability_shift = SCAValidator::LIABILITY_SHIFT_POSSIBLE;
                $threeDSecure->enrollment_status = SCAValidator::ENROLLMENT_STATUS_BYPASS;
                $threeDSecure->authentication_status = '';
                break;

            default:
                // Return null for unknown scenarios (should allow payment)
                return null;
        }

        $authResponse->three_d_secure = $threeDSecure;
        return $authResponse;
    }
}
