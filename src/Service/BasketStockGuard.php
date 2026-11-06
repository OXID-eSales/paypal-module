<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Brings the basket in line with the current stock before the customer pays for it.
 *
 * Between putting an article into the basket and paying, another customer can buy its
 * last unit. Nothing in the checkout notices that on its own: the basket keeps the article
 * and its old total until the stock check inside finalizeOrder() - which for an express
 * checkout comes only after the buyer approved the old amount at PayPal. This guard is
 * called wherever the basket is shown for confirmation or handed to a payment (order page,
 * order submit, the PayPal buttons) and corrects it right there:
 *
 * - an article that is no longer buyable (sold out and offline, deactivated, not orderable)
 *   is removed,
 * - an article with too little stock left is reduced to the remaining amount, or removed
 *   when nothing is left,
 *
 * each with a message that names the article, so the customer sees what changed and why.
 *
 * The check reads without locks. The authoritative check stays validateStock() inside the
 * finalize transaction (Model\Order); it names the article it removes with the same message
 * (reportRemovedItem() or getRemovedItemMessage()).
 */
class BasketStockGuard
{
    public const MESSAGE_REMOVED = 'OSC_PAYPAL_BASKET_ARTICLE_REMOVED';
    public const MESSAGE_REDUCED = 'OSC_PAYPAL_BASKET_ARTICLE_AMOUNT_REDUCED';

    /**
     * Corrects the basket to the current stock.
     *
     * @return bool true if the basket was changed; it is recalculated then and the
     *              messages are queued for the next page
     */
    public function correctBasket(Basket $basket): bool
    {
        $changed = false;

        // First drop what can no longer be bought at all: the stock check below asks every
        // other position for its article, and would fail on such a position.
        foreach ($basket->getContents() as $key => $basketItem) {
            if (!$this->isCheckedItem($basketItem)) {
                continue;
            }
            try {
                // reloads the article, so visibility and buyability reflect the current stock
                $basketItem->getArticle(true, null, true);
            } catch (NoArticleException | ArticleInputException $exception) {
                $basket->removeItem($key);
                $this->reportRemovedItem($basketItem);
                $changed = true;
            }
        }

        foreach ($basket->getContents() as $key => $basketItem) {
            if (!$this->isCheckedItem($basketItem)) {
                continue;
            }
            $article = $basketItem->getArticle(true);
            $onStock = $article->checkForStock(
                $basketItem->getAmount(),
                $basket->getArtStockInBasket($article->getId(), $key)
            );
            if ($onStock === true) {
                continue;
            }

            $changed = true;
            $remaining = is_numeric($onStock) ? (float) $onStock : 0.0;
            if ($remaining > 0 && $this->reduceAmount($basketItem, $remaining, (string) $key)) {
                $this->addMessage(self::MESSAGE_REDUCED, $basketItem->getTitle(), $basketItem->getAmount());
                continue;
            }
            $basket->removeItem($key);
            $this->reportRemovedItem($basketItem);
        }

        if ($changed) {
            $basket->onUpdate();
            $basket->calculateBasket(true);
        }

        return $changed;
    }

    /**
     * Queues the message for a position that was removed because its article is no longer
     * available. Also used by validateStock(), which removes such positions itself.
     */
    public function reportRemovedItem(BasketItem $basketItem): void
    {
        $this->addMessage(self::MESSAGE_REMOVED, $basketItem->getTitle());
    }

    /**
     * The message for a removed position, for callers that hand it on in an exception.
     */
    public function getRemovedItemMessage(BasketItem $basketItem): string
    {
        return $this->formatMessage(self::MESSAGE_REMOVED, $basketItem->getTitle());
    }

    /**
     * Bundled and discount articles are added by the basket calculation itself and follow
     * the positions they belong to.
     */
    private function isCheckedItem($basketItem): bool
    {
        return $basketItem instanceof BasketItem
            && !$basketItem->isBundle()
            && !$basketItem->isDiscountArticle();
    }

    private function reduceAmount(BasketItem $basketItem, float $remaining, string $key): bool
    {
        try {
            // checks the stock again and caps the amount, as the core does in the basket
            $basketItem->setAmount($remaining, true, $key);
        } catch (StandardException $exception) {
            // e.g. a remaining amount the article's packaging rules do not allow
            return false;
        }

        return $basketItem->getAmount() > 0;
    }

    /**
     * @param string|float ...$arguments
     */
    private function addMessage(string $ident, ...$arguments): void
    {
        Registry::getUtilsView()->addErrorToDisplay($this->formatMessage($ident, ...$arguments), false, true);
    }

    /**
     * @param string|float ...$arguments
     */
    private function formatMessage(string $ident, ...$arguments): string
    {
        $lang = Registry::getLang();
        $arguments = array_map(
            static function ($argument): string {
                if (is_float($argument)) {
                    // 2.0 -> "2", 1.5 -> "1.5"
                    $argument = rtrim(rtrim(sprintf('%.3F', $argument), '0'), '.');
                }
                // the themes print messages unescaped, and the title is merchant content
                return htmlspecialchars((string) $argument, ENT_QUOTES);
            },
            $arguments
        );

        return vsprintf($lang->translateString($ident, (int) $lang->getBaseLanguage(), false), $arguments);
    }
}
