<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Service;

use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings as ModuleSettingsService;
use OxidSolutionCatalysts\PayPal\Service\SCAValidator;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as ApiOrderModel;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse;
use PHPUnit\Framework\MockObject\MockObject;

final class SCAValidatorTest extends BaseTestCase
{
    private function createSuccess3DCardOrder(): ApiOrderModel
    {
        $order = new ApiOrderModel();
        $order->id = 'some_id';

        $paymentSource = new PaymentSourceResponse();
        $card = new CardResponse();
        $card->last_digits = '7704';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $auth = new AuthenticationResponse();
        $auth->liability_shift = 'POSSIBLE';

        $threeDS = new ThreeDSecureAuthenticationResponse();
        $threeDS->authentication_status = 'Y';
        $threeDS->enrollment_status     = 'Y';

        $auth->three_d_secure = $threeDS;
        $card->authentication_result = $auth;
        $paymentSource->card = $card;

        $order->payment_source = $paymentSource;
        return $order;
    }

    private function createFailedAuthenticationOrder(): ApiOrderModel
    {
        $order = new ApiOrderModel();

        $paymentSource = new PaymentSourceResponse();
        $card = new CardResponse();
        $card->last_digits = '2421';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';

        $auth = new AuthenticationResponse();
        $auth->liability_shift = 'NO';

        $threeDS = new ThreeDSecureAuthenticationResponse();
        $threeDS->authentication_status = 'N';
        $threeDS->enrollment_status     = 'Y';

        $auth->three_d_secure = $threeDS;
        $card->authentication_result = $auth;
        $paymentSource->card = $card;

        $order->payment_source = $paymentSource;
        return $order;
    }

    private function createMissingCardAuthenticationOrder(): ApiOrderModel
    {
        $order = new ApiOrderModel();

        $paymentSource = new PaymentSourceResponse();
        $card = new CardResponse();
        $card->last_digits = '9760';
        $card->brand = 'VISA';
        $card->type = 'CREDIT';
        // No authentication_result, so "missing" SCA data.

        $paymentSource->card = $card;
        $order->payment_source = $paymentSource;
        return $order;
    }

    public function dataProviderVerify3D(): array
    {
        return [
            'success' => [
                'paymentId' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'paypalOrder' => $this->createSuccess3DCardOrder(),
                'alwaysIgnoreSCAResult' => false,
                'assert' => 'assertTrue',
                'sca' => Constants::PAYPAL_SCA_ALWAYS
            ],
            'fail' => [
                'paymentId' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'paypalOrder' => $this->createFailedAuthenticationOrder(),
                'alwaysIgnoreSCAResult' => false,
                'assert' => 'assertFalse',
                'sca' => Constants::PAYPAL_SCA_ALWAYS
            ],
            'other_payment' => [
                'paymentId' => PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID,
                'paypalOrder' => $this->createFailedAuthenticationOrder(),
                'alwaysIgnoreSCAResult' => false,
                'assert' => 'assertTrue',
                'sca' => Constants::PAYPAL_SCA_ALWAYS
            ],
            'ignore_sca' => [
                'paymentId' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'paypalOrder' => $this->createFailedAuthenticationOrder(),
                'alwaysIgnoreSCAResult' => true,
                'assert' => 'assertTrue',
                'sca' => Constants::PAYPAL_SCA_WHEN_REQUIRED
            ],
            'sca_automatic_empty_result' => [
                'paymentId' => PayPalDefinitions::ACDC_PAYPAL_PAYMENT_ID,
                'paypalOrder' => $this->createMissingCardAuthenticationOrder(),
                'alwaysIgnoreSCAResult' => false,
                'assert' => 'assertTrue',
                'sca' => Constants::PAYPAL_SCA_WHEN_REQUIRED
            ]
        ];
    }

    /**
     * @dataProvider dataProviderVerify3D
     */
    public function testVerify3D(
        string $paymentId,
        ApiOrderModel $paypalOrder,
        bool $alwaysIgnoreSCAResult,
        string $assert,
        string $sca
    ): void {
        /** @var ModuleSettingsService|MockObject $moduleSettingsService */
        $moduleSettingsService = $this->getMockBuilder(ModuleSettingsService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $moduleSettingsService->expects($this->any())
            ->method('getPayPalSCAContingency')
            ->willReturn($sca);

        $moduleSettingsService->expects($this->any())
            ->method('alwaysIgnoreSCAResult')
            ->willReturn($alwaysIgnoreSCAResult);

        $serviceFactory = $this->getMockBuilder(ServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $validator = new SCAValidator($serviceFactory, $moduleSettingsService);

        $this->$assert(
            $validator->verify3D($paymentId, $paypalOrder)
        );
    }
}
