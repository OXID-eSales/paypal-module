<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Api;

use OxidSolutionCatalysts\PayPalApi\Service\BaseService;
use Psr\Http\Message\ResponseInterface;

class IdentityService extends BaseService
{
    protected $basePath = '/v1/identity';

    public function requestClientToken(): array
    {
        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $headers = array_merge($headers, $this->getAuthHeaders());

        $path = '/generate-token';
        $method = 'post';

        /** @var ResponseInterface $response */
        $response = $this->send($method, $path, [], $headers);
        $body = $response->getBody();

        return $body ? json_decode((string)$body, true) : [];
    }

    /**
     * @return array
     */
    protected function getAuthHeaders(): array
    {
        if (!$this->client->isAuthenticated()) {
            $this->client->auth();
        }

        $headers = [];
        $headers['Authorization'] = 'Bearer ' . $this->client->getTokenResponse();

        return $headers;
    }
}
