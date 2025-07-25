<?php

namespace OxidSolutionCatalysts\PayPalApi;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use OxidSolutionCatalysts\PayPalApi\Exception\ApiException;
use Psr\Log\LoggerInterface;

class Onboarding extends Client
{
    /**
     * @var string
     */
    private $partnerId;

    /**
     * @var string
     */
    private $sellerId;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * during onboarding you do not have shop owners credentials
     * so you initialize the client with the technical oxid account credentials
     * Onboarding constructor.
     * @param LoggerInterface $logger
     * @param string $endpoint the base API url
     * @param string $oxidClientId the client id from the technical oxid account
     * @param string $oxidClientSecret the client secret from the technical oxid account
     * @param string $oxidPartnerId for getting credentials and information for successful onboarding
     * @param string $sellerId for getting information for successful onboarding
     * @param string $tokenCacheFilename the filename for the cached token
     * @param string $actionHash - An hash to help generate a unique PayPal-Request-Id
     * @param bool $debug  set to true to debug request sent to PayPal on the console
     */
    public function __construct(
        LoggerInterface $logger,
                        $endpoint,
                        $oxidClientId,
                        $oxidClientSecret,
                        $oxidPartnerId,
                        $sellerId,
                        $tokenCacheFilename,
                        $actionHash,
                        $debug = false
    ) {
        $this->partnerId = $oxidPartnerId;
        $this->sellerId = $sellerId;
        $this->logger = $logger;
        parent::__construct(
            $logger,
            $endpoint,
            $oxidClientId,
            $oxidClientSecret,
            $tokenCacheFilename,
            $actionHash,
            '',
            $debug
        );
    }

    /**
     * Auth after seller used on browser to login into the paypal account
     * the parameters are usually provided in a callback on client side e.g.:
     * <script>
     * function onboardedCallback(authCode, sharedId) {
     * // post data to the server side post(authCode, sharedId)
     * }
     * </script>
     * @param $authCode string this is returned by paypal in the call back function
     * @param $sharedId string this is returned by paypal in the call back function
     * @param $sellerNonce string the random number that was used to generate the paypal register/login link
     * @throws ApiException
     */
    public function authAfterWebLogin(string $authCode, string $sharedId, string $sellerNonce): void
    {
        //fixme: test this by using register link and callback see 2.1.3.1 in paypal sdd 1.0
        $authBase64 = base64_encode("$sharedId:");
        $client = $this->httpClient;
        $url = $this->endpoint . "/v1/oauth2/token";
        $headers = [
            "Authorization" => "Basic $authBase64",
            "Accept" => self::CONTENT_TYPE_JSON
        ];

        try {
            $res = $client->post($url, [
                "headers" => $headers,
                'form_params' => [
                    "grant_type" => "authorization_code",
                    "code" => $authCode,
                    "code_verifier" => $sellerNonce,
                ]
            ]);
            $rawTokenResponse = (array)json_decode('' . $res->getBody(), true, 512, JSON_THROW_ON_ERROR);

        } catch (GuzzleException $exception) {
            $this->logger->log('error', $exception->getMessage(), [$exception]);
            throw new ApiException($exception);
        } catch (JsonException $e) {
            $rawTokenResponse = [];
        }

        $this->setTokenResponse($rawTokenResponse['access_token'] ?: '');
    }

    /**
     * @throws ApiException
     */
    public function getCredentials(): array
    {
        $partnerId = $this->partnerId;
        $request = $this->createRequest(
            'GET',
            "/v1/customer/partners/{$partnerId}/merchant-integrations/credentials",
            []
        );

        try {
            $response = $this->send($request);
            $result = (array) json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException $exception) {
            $this->logger->log('error', $exception->getMessage(), [$exception]);
            throw new ApiException($exception);
        } catch (JsonException $e) {
            $result = [];
        }
        return $result;
    }

    /**
     * @throws ApiException
     */
    public function getMerchantInformations(): array
    {
        $partnerId = $this->partnerId;
        $sellerId = $this->sellerId;
        $request = $this->createRequest(
            'GET',
            "/v1/customer/partners/{$partnerId}/merchant-integrations/{$sellerId}",
            []
        );

        try {
            $response = $this->send($request);
            $result = (array) json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException $exception) {
            $this->logger->log('error', $exception->getMessage(), [$exception]);
            throw new ApiException($exception);
        } catch (JsonException $e) {
            $result = [];
        }
        return $result;
    }
}