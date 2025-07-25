<?php

namespace OxidSolutionCatalysts\PayPalApi\Service;

use GuzzleHttp\Exception\GuzzleException;
use OxidSolutionCatalysts\PayPalApi\Client;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Query;

class BaseService
{
    public Client $client;

    protected string $basePath = '';

    /**
     * @param $client Client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $method
     * @param string $path
     * @param array $params
     * @param array<string,string> $headers
     * @param null|string $body
     * @return ResponseInterface
     * @throws ApiException
     */
    protected function send($method, $path, $params = [], $headers = [], $body = null): ResponseInterface
    {
        $logger = $this->client->getLogger();

        $params = array_filter($params);
        if ($params) {
            $q = Query::build($params);
            $path = "$path?$q";
        }
        $fullPath = $this->basePath . $path;

        $headers['PayPal-Request-Id'] = md5($path . serialize($body) . $this->client->getActionHash());

        $request = $this->client->createRequest($method, $fullPath, $headers, $body);

        $logger->log('debug', 'PayPal SEND path ' . $path);
        $logger->log('debug', 'PayPal SEND request ' . $request->getBody());
        $logger->log('debug', 'PayPal SEND headers ' . serialize($request->getHeaders()));

        try {
            $response = $this->client->send($request);
        } catch (GuzzleException $exception) {
            $logger->log('error', $exception->getMessage(), [$exception]);
            throw new ApiException($exception);
        }
        return $response;
    }
}
