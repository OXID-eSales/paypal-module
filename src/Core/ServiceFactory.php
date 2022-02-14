<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPalApi\Client;
use OxidSolutionCatalysts\PayPalApi\Service\Catalog;
use OxidSolutionCatalysts\PayPalApi\Service\GenericService;
use OxidSolutionCatalysts\PayPalApi\Service\Orders;
use OxidSolutionCatalysts\PayPalApi\Service\Payments;
use OxidSolutionCatalysts\PayPalApi\Service\Subscriptions;
use OxidSolutionCatalysts\PayPalApi\Service\TransactionSearch;
use OxidSolutionCatalysts\PayPal\Core\Api\DisputeService as FileAwareDisputeService;
use OxidSolutionCatalysts\PayPal\Core\Api\DisputeService;
use OxidSolutionCatalysts\PayPal\Core\Api\IdentityService;

/**
 * Class ServiceFactory
 * @package OxidSolutionCatalysts\PayPal\Core
 *
 * Responsible for creation of PayPal service objects
 */
class ServiceFactory
{
    /**
     * @var Client
     */
    private $client;

    public function getSubscriptionService(): Subscriptions
    {
        return oxNew(Subscriptions::class, $this->getClient());
    }

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
     * @return Catalog
     */
    public function getCatalogService(): Catalog
    {
        return new Catalog($this->getClient());
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
    public function geWebhookService(): GenericService
    {
        return oxNew(
            GenericService::class,
            $this->getClient(),
            '/v1/notifications/webhooks'
        );
    }

    /**
     * @return TransactionSearch
     */
    public function getTransactionSearchService(): TransactionSearch
    {
        return oxNew(TransactionSearch::class, $this->getClient());
    }

    /**
     * @return DisputeService
     */
    public function getDisputeService(): DisputeService
    {
        return oxNew(DisputeService::class, $this->getClient());
    }

    /**
     * @return DisputeService
     */
    public function getFileAwareDisputeService(): FileAwareDisputeService
    {
        return oxNew(FileAwareDisputeService::class, $this->getClient());
    }

    /**
     * @return GenericService
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

            $client = new Client(
                Registry::getLogger(),
                $config->isSandbox() ? Client::SANDBOX_URL : Client::PRODUCTION_URL,
                $config->getClientId(),
                $config->getClientSecret(),
                '',
                // must be empty. We do not have the merchant's payerid
                //and confirmed by paypal we should not use it for auth and
                //so not ask for it on the configuration page
                false
            );
            //fixme: auth needs to be injected to avoid slow authentification
            //the token value should be stored in the db/oxconfig and it is valid for 8 hours

            $this->client = $client;
        }

        return $this->client;
    }
}
