<?php // phpcs:ignoreFile

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

use OxidEsales\Facts\Facts;
use OxidEsales\Eshop\Core\ConfigFile;
use OxidEsales\TestingLibrary\Services\Library\DatabaseDefaultsFileGenerator;

$facts = new Facts();

$phpBinEnv = (getenv('PHPBIN')) ? : 'php';

$screenShotPathEnv = getenv('CC_SCREEN_SHOTS_PATH');
$screenShotPathEnv = ($screenShotPathEnv) ? : '';

return [
    'SHOP_URL' => $facts->getShopUrl(),
    'SHOP_SOURCE_PATH' => $facts->getSourcePath(),
    'VENDOR_PATH' => $facts->getVendorPath(),
    'DB_NAME' => $facts->getDatabaseName(),
    'DB_USERNAME' => $facts->getDatabaseUserName(),
    'DB_PASSWORD' => $facts->getDatabasePassword(),
    'DB_HOST' => $facts->getDatabaseHost(),
    'DB_PORT' => $facts->getDatabasePort(),
    'DUMP_PATH' => getTestDataDumpFilePath(),
    'MODULE_DUMP_PATH' => getModuleTestDataDumpFilePath(),
    'MYSQL_CONFIG_PATH' => getMysqlConfigPath(),
    'SELENIUM_SERVER_PORT' => getenv('SELENIUM_SERVER_PORT') ?: '4444',
    'SELENIUM_SERVER_IP' => getenv('SELENIUM_SERVER_IP') ?: 'selenium',
    'BROWSER_NAME' => getenv('BROWSER_NAME') ?: 'chrome',
    'PHP_BIN' => $phpBinEnv,
    'SCREEN_SHOT_URL' => $screenShotPathEnv
];

function getTestDataDumpFilePath()
{
    return getShopTestPath() . '/Codeception/_data/dump.sql';
}

function getModuleTestDataDumpFilePath()
{
    return __DIR__ . '/../_data/dump.sql';
}

function getShopSuitePath($facts)
{
    $testSuitePath = getenv('TEST_SUITE');
    if (!$testSuitePath) {
        $testSuitePath = $facts->getShopRootPath() . '/tests';
    }

    return $testSuitePath;
}

function getShopTestPath()
{
    $facts = new Facts();

    if ($facts->isEnterprise()) {
        $shopTestPath = $facts->getEnterpriseEditionRootPath() . '/Tests';
    } else {
        $shopTestPath = getShopSuitePath($facts);
    }

    return $shopTestPath;
}

function getMysqlConfigPath()
{
    $facts = new Facts();
    $configFile = new ConfigFile($facts->getSourcePath() . '/config.inc.php');

    $generator = new DatabaseDefaultsFileGenerator($configFile);

    return $generator->generate();
}
