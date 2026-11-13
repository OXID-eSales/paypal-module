<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Trait;

use OxidEsales\Eshop\Application\Model\Article as EshopModelArticle;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;

/**
 * Creates the article and the German customer that the order request tests
 * need, instead of relying on demo data that a test installation does not
 * have. The ids start with an underscore, so cleanUpTable() removes them.
 */
trait TestBasketDataTrait
{
    protected string $testArticleId = '_test_paypal_article';
    protected string $testUserId = '_test_paypal_user';

    protected function createTestArticle(): string
    {
        $article = oxNew(EshopModelArticle::class);
        $article->setId($this->testArticleId);
        $article->assign([
            'oxshopid' => 1,
            'oxactive' => 1,
            'oxartnum' => '_TEST_PAYPAL_1',
            'oxtitle' => 'PayPal test article',
            'oxprice' => 34.00,
            'oxstock' => 100,
            'oxstockflag' => 1,
        ]);
        $article->save();

        return $this->testArticleId;
    }

    /**
     * German customer without a phone number: a PUI request needs the phone
     * number from the request then.
     */
    protected function createTestUser(): EshopModelUser
    {
        $user = oxNew(EshopModelUser::class);
        $user->setId($this->testUserId);
        $user->assign([
            'oxshopid' => 1,
            'oxactive' => 1,
            'oxrights' => 'user',
            'oxusername' => 'paypal-test-user@example.test',
            'oxsal' => 'MR',
            'oxfname' => 'Test',
            'oxlname' => 'Customer',
            'oxstreet' => 'Teststrasse',
            'oxstreetnr' => '1',
            'oxzip' => '10115',
            'oxcity' => 'Berlin',
            'oxcountryid' => 'a7c40f631fc920687.20179984',
        ]);
        $user->save();

        $user->load($this->testUserId);

        return $user;
    }

    protected function removeTestBasketData(): void
    {
        $this->cleanUpTable('oxarticles');
        $this->cleanUpTable('oxuser');
    }
}
