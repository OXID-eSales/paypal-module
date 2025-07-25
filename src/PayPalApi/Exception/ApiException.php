<?php

namespace OxidSolutionCatalysts\PayPalApi\Exception;

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ApiException extends \Exception
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(GuzzleException $e)
    {
        $code = $e->getCode();
        $this->request = $e->getRequest();
        $this->response = $e->getResponse();
        $phrase = $this->response->getReasonPhrase();
        $message = $e->getRequest()->getMethod() . ' ' . $e->getRequest()->getUri() . " returned: $code $phrase";
        $error = json_decode($e->getResponse()->getBody(), true);
        if ($error) {
            if (isset($error['message'])) {
                $message .= "\nReturned Message: " . $error['message'];
            }
            if (isset($error['details'])) {
                $details = $error['details'];
                $message .= "\nError Details: \n" . json_encode($details) . "\n";
                unset($error['details']);
            }
            unset($error['message']);
            $message .= "\nResponse: \n" . json_encode($error) . "\n";
        }


        $message .= "\nThe following curl request could be used to simulate a similar request:
        \ncurl -v -X " . $this->request->getMethod() . ' "' . $this->request->getUri() . '"';
        foreach ($this->request->getHeaders() as $headerName => $headerValue) {
            $message .= " -H \"$headerName: " . join(",", $headerValue) . '"';
        }
        if ($this->request->getBody() . "") {
            $message .= " -d " . $this->request->getBody();
        }
        parent::__construct($message, $code);
    }

    /**
     * Checks if the exception information should be visible to end user
     *
     * @return bool
     */
    public function shouldDisplay()
    {
        return true;
    }

    /**
     * Gets error description
     *
     * @return string
     */
    public function getErrorDescription()
    {
        $description = '';

        if ($error = json_decode($this->response->getBody(), true)) {
            $details = $error['details'][0];
            $description = $details['description'];
            if (!$description) {
                $description = $error['message'];
            }
        }

        return $description;
    }

    /**
     * Gets error issue (better for translation ...)
     *
     * @return string
     */
    public function getErrorIssue()
    {
        $issue = '';

        if ($error = json_decode($this->response->getBody(), true)) {
            $details = $error['details'][0];
            $issue = $details['issue'];
        }

        return $issue;
    }
}
