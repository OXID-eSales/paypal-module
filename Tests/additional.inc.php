<?php

declare(strict_types=1);

use OxidEsales\Facts\Facts;

$serviceCaller = oxNew(\OxidEsales\TestingLibrary\ServiceCaller::class);
$testConfig    = oxNew(\OxidEsales\TestingLibrary\TestConfig::class);

$testDirectory = $testConfig->getEditionTestsPath($testConfig->getShopEdition());
$serviceCaller->setParameter('importSql', '@' . $testDirectory . '/Fixtures/testdemodata.sql');
$serviceCaller->callService('ShopPreparation', 1);
