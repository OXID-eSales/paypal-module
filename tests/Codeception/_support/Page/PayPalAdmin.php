<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Page;

use OxidEsales\Codeception\Admin\AdminPanel;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Page;

class PayPalAdmin extends AdminPanel
{
    public function openConfiguration(): Page
    {
        $I = $this->user;

        $I->selectNavigationFrame();
        $I->retryClick(Translator::translate('paypal'));
        $I->seeElement('//li[@name="nav_oscpaypalconfig"]//a');
        $I->amOnUrl($I->grabAttributeFrom('//li[@name="nav_oscpaypalconfig"]//a', 'href'));
        $I->waitForDocumentReadyState();

        return new Page($I);
    }

    public function openTransactions(): Page
    {
        $I = $this->user;

        $I->selectNavigationFrame();
        $I->retryClick(Translator::translate('paypal'));
        $I->seeElement('//li[@name="nav_oscpaypaltransactions"]//a');
        $I->amOnUrl($I->grabAttributeFrom('//li[@name="nav_oscpaypaltransactions"]//a', 'href'));
        $I->waitForDocumentReadyState();

        return new Page($I);
    }

    public function openBalances(): Page
    {
        $I = $this->user;

        $I->selectNavigationFrame();
        $I->retryClick(Translator::translate('paypal'));
        $I->seeElement('//li[@name="nav_oscpaypalbalance"]//a');
        $I->amOnUrl($I->grabAttributeFrom('//li[@name="nav_oscpaypalbalance"]//a', 'href'));
        $I->waitForDocumentReadyState();

        return new Page($I);
    }

    public function openSubscriptions(): Page
    {
        $I = $this->user;

        $I->selectNavigationFrame();
        $I->retryClick(Translator::translate('paypal'));
        $I->seeElement('//li[@name="nav_oscpaypalsubscription"]//a');
        $I->amOnUrl($I->grabAttributeFrom('//li[@name="nav_oscpaypalsubscription"]//a', 'href'));
        $I->waitForDocumentReadyState();

        return new Page($I);
    }

    public function openDisputes(): Page
    {
        $I = $this->user;

        $I->selectNavigationFrame();
        $I->retryClick(Translator::translate('paypal'));
        $I->seeElement('//li[@name="nav_oscpaypaldispute"]//a');
        $I->amOnUrl($I->grabAttributeFrom('//li[@name="nav_oscpaypaldispute"]//a', 'href'));
        $I->waitForDocumentReadyState();

        return new Page($I);
    }

    public function grabConfigurationLink(): string
    {
        $I = $this->user;

        $I->selectNavigationFrame();
        $I->retryClick(Translator::translate('paypal'));
        $I->seeElement('//li[@name="nav_oscpaypalconfig"]//a');

        return $I->grabAttributeFrom('//li[@name="nav_oscpaypalconfig"]//a', 'href');
    }
}
