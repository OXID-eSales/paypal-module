<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page;

use OxidEsales\Codeception\Page\Component\Footer\Footer;
use OxidEsales\Codeception\Page\Component\Header\Header;
use OxidEsales\Codeception\Page\Component\Widget\Promotion;
use OxidEsales\Codeception\Page\Lists\ProductList;

class Home extends Page
{
    use Header;
    use Footer;

    public string $URL = '/';
    private string $openManufacturerList = '//div[@class="row manufacturer-list"]/div[%s]';

    private string $getNewestArticles = 'newItems';
    private string $getTop5ArticleList = 'topBox';
    private string $getBargainArticleList = 'bargainItems';

    public function openManufacturerFromStarPage(string $manufacturerTitle, int $position = 1): ProductList
    {
        $I = $this->user;
        $productListPage = new ProductList($I);
        $I->retryMoveMouseOver(sprintf($this->openManufacturerList, $position));
        $I->retryClick(sprintf($this->openManufacturerList, $position));
        $I->waitForPageLoad();
        return $productListPage;
    }

    public function getNewestArticles(): Promotion
    {
        return $this->getPromotion($this->getNewestArticles);
    }

    public function getPromotionTop5(): Promotion
    {
        return $this->getPromotion($this->getTop5ArticleList);
    }

    public function getBargainArticleList(): Promotion
    {
        return $this->getPromotion($this->getBargainArticleList);
    }

    public function getPromotion(string $widgetId): Promotion
    {
        $I = $this->user;
        return new Promotion($I, $widgetId);
    }
}
