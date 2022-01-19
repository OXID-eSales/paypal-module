<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Acceptance;

use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSetupException;
use OxidEsales\Facts\Facts;

abstract class BaseCest
{
    public function _before(AcceptanceTester $I): void
    {
        $this->activateModules();
    }

    public function _after(AcceptanceTester $I): void
    {
    }

    protected function getShopUrl(): string
    {
        $facts = new Facts();

        return $facts->getShopUrl();
    }

    /**
     * Activates modules
     */
    protected function activateModules(int $shopId = 1): void
    {
        $testConfig        = new \OxidEsales\TestingLibrary\TestConfig();
        $modulesToActivate = $testConfig->getModulesToActivate();

        if ($modulesToActivate) {
            $serviceCaller = new \OxidEsales\TestingLibrary\ServiceCaller();
            $serviceCaller->setParameter('modulestoactivate', $modulesToActivate);

            try {
                $serviceCaller->callService('ModuleInstaller', $shopId);
            } catch (ModuleSetupException $e) {
                // this may happen if the module is already active,
                // we can ignore this
            }
        }
    }
}
