<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Module;

use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use Codeception\Module\REST;

class PayPal extends Module implements DependsOnModule
{
    /** @var REST */
    private $rest;

    /**
     * @return array|mixed
     */
    public function _depends()
    {
        return [REST::class => 'Codeception\Module\REST is required'];
    }

    public function _inject(REST $rest): void
    {
        $this->rest = $rest;
    }

    public function getRest(): REST
    {
        return $this->rest;
    }

    public function postTo(string $url, array $headers = []): void
    {
        foreach ($headers as $name => $value) {
            $this->rest->haveHTTPHeader($name, $value);
        }

        $this->rest->haveHTTPHeader('Content-Type', 'application/json');
        $this->rest->sendPOST($url);
    }

    public function grabJsonResponseAsArray(): array
    {
        return json_decode($this->rest->grabResponse(), true);
    }

    public function grabResponseCookies(): array
    {
        return $this->rest->grabHttpHeader('Set-Cookie', false);
    }

    public function extractSidFromResponseCookies(): string
    {
        $cookieHeaders = $this->grabResponseCookies();

        $sid = '';
        foreach ($cookieHeaders as $value) {
            preg_match('/^(sid=)([a-z0-9]*);/', $value, $matches);
            if (isset($matches[2])) {
                $sid = $matches[2];
                break;
            }
        }

        return $sid;
    }
}