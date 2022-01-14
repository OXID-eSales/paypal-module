<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception;

use OxidEsales\Codeception\Page\Home;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use ServiceContainer;

    use _generated\AcceptanceTesterActions;

    /**
     * Define custom actions here
     */

    /**
     * @param $value mixed
     */
    public function saveModuleConfigVariable(string $confName, $value): void
    {
        $this->getServiceFromContainer(ModuleSettings::class)->save($confName, $value);
    }

    /**
     * Open shop first page.
     */
    public function openShop(): Home
    {
        $I = $this;
        $homePage = new Home($I);
        $I->amOnPage($homePage->URL);

        return $homePage;
    }
}