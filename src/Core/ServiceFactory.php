<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\Api\VaultingService;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Client;
use OxidSolutionCatalysts\PayPalApi\Service\Partner;
use OxidSolutionCatalysts\PayPalApi\Service\GenericService;
use OxidSolutionCatalysts\PayPalApi\Service\Orders;
use OxidSolutionCatalysts\PayPalApi\Service\Payments;
use OxidSolutionCatalysts\PayPal\Core\Api\IdentityService;
use Psr\Log\LoggerInterface;

/**
 * Class ServiceFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 *
 * Responsible for creation of PayPal service objects
 */
class ServiceFactory
{
    use ServiceContainer;

    /**
     * @var Client
     */
    private $client;

    /**
     * @return Payments
     */
    public function getPaymentService(): Payments
    {
        return oxNew(Payments::class, $this->getClient());
    }

    /**
     * @return Orders
     */
    public function getOrderService(): Orders
    {
        return oxNew(Orders::class, $this->getClient());
    }

    /**
     * @return GenericService
     */
    public function getNotificationService(): GenericService
    {
        return oxNew(
            GenericService::class,
            $this->getClient(),
            '/v1/notifications/verify-webhook-signature'
        );
    }

    /**
     * @return GenericService
     */
    public function getWebhookService(string $uri = ''): GenericService
    {
        return oxNew(
            GenericService::class,
            $this->getClient(),
            '/v1/notifications/webhooks' . $uri
        );
    }

    /**
     * @return GenericService
     */
    public function getTrackerService(): GenericService
    {
        return oxNew(
            GenericService::class,
            $this->getClient(),
            '/v1/shipping/trackers-batch'
        );
    }

    /**
     * @return VaultingService
     */
    public function getVaultingService(): VaultingService
    {
        return oxNew(
            VaultingService::class,
            $this->getClient()
        );
    }

    /**
     * @return Partner
     */
    public function getPartnerService(): Partner
    {
        return oxNew(
            Partner::class,
            $this->getClient()
        );
    }

    /**
     * @return IdentityService
     */
    public function getIdentityService(): IdentityService
    {
        return oxNew(
            IdentityService::class,
            $this->getClient()
        );
    }

    /**
     * Get PayPal client object
     *
     * @return Client
     */
    private function getClient(): Client
    {
        if ($this->client === null) {
            /** @var Config $config */
            $config = oxNew(Config::class);
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceFromContainer('OxidSolutionCatalysts\PayPal\Logger');

            $debug = Registry::getConfig()->getConfigParam('sLogLevel') === 'debug';

            // prepare a unique action hash
            $session = Registry::getSession();
            $sessionId = $session->getId();
            $basketId = $session->getVariable('sess_challenge');
            $paymentId = $session->getVariable('paymentid');
            $actionHash = md5($sessionId . $basketId . $paymentId);

            $client = new Client(
                $logger,
                $config->isSandbox() ? Client::SANDBOX_URL : Client::PRODUCTION_URL,
                $config->getClientId(),
                $config->getClientSecret(),
                $config->getTokenCacheFileName(),
                $actionHash,
                // must be empty. We do not have the merchant's payerid
                //and confirmed by paypal we should not use it for auth and
                //so not ask for it on the configuration page
                '',
                false
            );

            $this->client = $client;
        }

        return $this->client;
    }
}
