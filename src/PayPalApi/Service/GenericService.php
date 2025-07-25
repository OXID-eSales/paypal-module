<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPalApi\Service;

use OxidSolutionCatalysts\PayPalApi\Client;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use OxidSolutionCatalysts\PayPalApi\Model\Payments\Authorization;

class GenericService extends BaseService
{
    /**
     * Headers that are automatically appended for requests
     *
     * @var array
     */
    private $headers;

    /**
     * Request path
     *
     * @var string|null
     */
    private $path;

    /**
     * GenericService constructor.
     *
     * @param Client $client
     * @param array|null $headers
     * @param string|null $path
     */
    public function __construct(Client $client, string $path = null, array $headers = null)
    {
        $this->headers = $headers ?? [];
        $this->path = $path ?? null;

        parent::__construct($client);
    }

    /**
     * @param $method
     * @param array $params
     * @param array|null $body
     *
     * @param array $headers
     *
     * @return array
     * @throws ApiException
     */
    public function request($method, $body = null, $params = [], $headers = []): array
    {
        $requestHeaders = $this->headers;
        $requestHeaders['Content-Type'] = 'application/json';
        $requestHeaders = array_merge($requestHeaders, $headers);

        $body = $body == null ?: json_encode($body);

        $response = $this->send($method, $this->path, $params, $requestHeaders, $body);
        $return = json_decode($response->getBody(), true);

        if (is_bool($return) || is_null($return)) {
            $return = [];
        }

        return $return;
    }
}
