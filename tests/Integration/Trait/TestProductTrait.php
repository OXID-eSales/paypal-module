<?php

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Trait;

use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactory;

trait TestProductTrait
{
    protected string $testProductOxid = '1000';
    private string $articleNumber = '1000';
}
