<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Account;

use Codeception\Util\Locator;
use OxidEsales\Codeception\Page\Component\Modal;
use OxidEsales\Codeception\Page\Component\Pagination;
use OxidEsales\Codeception\Page\Page;

class MyReviews extends Page
{
    use Pagination;
    use Modal;

    public const URL = '/index.php?lang=1&cl=account_reviewlist';

    public string $URL = self::URL;
    public string $breadCrumb = '.breadcrumb';
    public $headerTitle = 'h1';
    private $reviewEntry = '//div[@class="reviews-landscape"]/div[@class="card"]';
    private $deleteReviewBtn = '//div[@id="reviewName_%s"]//button';

    /** @param int $cnt */
    public function seeNumberOfReviews(int $cnt): self
    {
        $I = $this->user;
        $I->seeNumberOfElements($this->reviewEntry, $cnt);
        return $this;
    }

    public function deleteFirstReviewInList(): self
    {
        $this->deleteReviewInList(1);
        return $this;
    }

    public function deleteReviewInList(int $position): self
    {
        $I = $this->user;
        $I->click(sprintf($this->deleteReviewBtn, $position));
        $this->confirmDeletion();
        return $this;
    }
}
