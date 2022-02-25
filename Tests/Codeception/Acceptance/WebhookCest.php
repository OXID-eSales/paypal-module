<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use Psr\Http\Message\ResponseInterface;
use OxidSolutionCatalysts\PayPal\Core\Config as PayPalConfig;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidSolutionCatalysts\PayPalApi\Client as PayPalApiClient;
use OxidSolutionCatalysts\PayPalApi\Service\BaseService;
use OxidEsales\TestingLibrary\helpers\ExceptionLogFileHelper;

/**
 * @group osc_paypal
 * @group osc_paypal_webhook
 * @group osc_paypal_public
 */
final class WebhookCest extends BaseCest
{

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $exceptionLogHelper = new ExceptionLogFileHelper(OX_LOG_FILE);
        $exceptionLogHelper->clearExceptionLogFile();
    }

    public function _after(AcceptanceTester $I): void
    {
        $exceptionLogHelper = new ExceptionLogFileHelper(OX_LOG_FILE);
        $exceptionLogHelper->clearExceptionLogFile();

        parent::_after($I);
    }

    /**
     * NOTE: this test requires a public ssl url as webhook endpoint and log level info
     * In local development environment you can use 'ngrok http <localhost.local>' to get
     * the local shop exposed on a public ssl url.
     */
    public function simulateWebhookEvent(AcceptanceTester $I): void
    {
        $I->wantToTest('that the webhook endpoint can receive a request');

        /** @var PayPalConfig $paypalConfig */
        $paypalConfig = oxNew(PayPalConfig::class);
        $webhookUrl = $paypalConfig->getWebhookControllerUrl();

        if (false === strrpos($webhookUrl, 'https://')) {
            $I->markTestIncomplete('Need a public ssl url for running this test.');
        }

        //ensure log is empty and written to by this test
        $I->retryAssertEmpty(file_get_contents(OX_LOG_FILE));

        EshopRegistry::getLogger()->debug('START WEBHOOK TEST');
        $I->retryAssertStringContainsString('START WEBHOOK TEST', file_get_contents(OX_LOG_FILE));

        /** @var TestService $service */
        $service = $this->getApiService();

        $request = [
            'url' => $webhookUrl,
            'event_type' => 'CHECKOUT.ORDER.COMPLETED',
            'resource_version' => '2.0'
        ];
        $path = '/v1/notifications/simulate-event';

        $requestHeaders['Content-Type'] = 'application/json';
        $body = json_encode($request);

        /** @var ResponseInterface $response */
        $response = $service->request('post', $path, [], $requestHeaders, $body);

        //status code is always 202, so this does not yet verify we actually received that call
        $I->assertEquals('202', $response->getStatusCode());

        $I->wait(15); //give the webhook time to receive the request

        EshopRegistry::getLogger()->debug('END WEBHOOK TEST');
        $I->retryAssertStringContainsString('END WEBHOOK TEST', file_get_contents(OX_LOG_FILE));

        $I->assertStringNotContainsString('Missing required verification headers', file_get_contents(OX_LOG_FILE));
        $I->assertStringContainsString('PayPal Webhook request', file_get_contents(OX_LOG_FILE));
        $I->assertStringContainsString('PayPal Webhook headers', file_get_contents(OX_LOG_FILE));
    }

    private function getApiService(): TestService
    {
        return oxNew(
            TestService::class,
            $this->getClient()
        );
    }

    private function getClient(): PayPalApiClient
    {
        /** @var PayPalConfig $paypalConfig */
        $paypalConfig = oxNew(PayPalConfig::class);

        return new PayPalApiClient(
            EshopRegistry::getLogger(),
            PayPalApiClient::SANDBOX_URL,
            $paypalConfig->getClientId(),
            $paypalConfig->getClientSecret(),
            '',
            // must be empty. We do not have the merchant's payerid
            //and confirmed by paypal we should not use it for auth and
            //so not ask for it on the configuration page
            false
        );
    }
}

class TestService extends BaseService
{
    /**
     * @param string $method
     * @param string $path
     * @param array $params
     * @param array<string,string> $headers
     * @param null|string $body
     * @return ResponseInterface
     * @throws ApiException
     */
    public function request($method, $path, $params = [], $headers = [], $body = null): ResponseInterface
    {
        return $this->send($method, $path, $params, $headers, $body);
    }
}