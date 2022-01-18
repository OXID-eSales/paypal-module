<?php

namespace OxidProfessionalServices\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Registry;
use OxidProfessionalServices\PayPal\Api\Client;
use OxidProfessionalServices\PayPal\Api\Onboarding;
use OxidProfessionalServices\PayPal\Core\Config;
use OxidProfessionalServices\PayPal\Core\Webhook\EventCreation;

class OnboardingController extends AdminController
{
    /**
     * Get ClientID, ClientSecret, WebhookID
     */
    public function autoConfigurationFromCallback()
    {
        $in = file_get_contents('php://input');
        $callBackData = json_decode($in);
        $authCode = $callBackData->authCode;
        $sharedId = $callBackData->sharedId;
        $isSandbox = $callBackData->isSandBox;
        $nonce = Registry::getSession()->getVariable('PAYPAL_MODULE_NONCE');

        $config = new Config();
        $oxidConfig = Registry::getConfig();
        $oxidConfig->setConfigParam('blPayPalSandboxMode', $isSandbox);
        $url = $config->isSandbox() ? Client::SANDBOX_URL : Client::PRODUCTION_URL;

        $result = [];

        try {
            $client = new Onboarding(
                Registry::getLogger(),
                $url,
                $config->getTechnicalClientId(),
                $config->getTechnicalClientSecret(),
                $config->getTechnicalPartnerId()
            );
            $client->authAfterWebLogin($authCode, $sharedId, $nonce);
            Registry::getSession()->deleteVariable('PAYPAL_MODULE_NONCE');

            // save credentials
            $credentials = $client->getCredentials();

            if ($isSandbox) {
                $oxidConfig->setConfigParam('sPayPalSandboxClientId', $credentials['client_id']);
                $oxidConfig->setConfigParam('sPayPalSandboxClientSecret', $credentials['client_secret']);
            } else {
                $oxidConfig->setConfigParam('sPayPalClientId', $credentials['client_id']);
                $oxidConfig->setConfigParam('sPayPalClientSecret', $credentials['client_secret']);
            }
            $result = [
                'client_id'     => $credentials['client_id'],
                'client_secret' => $credentials['client_secret']
            ];

            // create WebHook and setup WebHookEvents
            $webHook = oxNew(EventCreation::class);
            $webHookResponse = $webHook->create();

            if ($isSandbox) {
                $oxidConfig->setConfigParam('sPayPalSandboxWebhookId', $webHookResponse['id']);
            } else {
                $oxidConfig->setConfigParam('sPayPalWebhookId', $webHookResponse['id']);
            }
            $result['webhook_id'] = $webHookResponse['id'];
        } catch (ApiException $exception) {
            Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }

        header('Content-Type: application/json; charset=UTF-8');
        Registry::getUtils()->showMessageAndExit(json_encode($result));
    }
}
