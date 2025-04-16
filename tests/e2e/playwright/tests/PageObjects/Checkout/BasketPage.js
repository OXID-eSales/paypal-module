// BasketPage.js
import { expect } from '@playwright/test';

export class BasketPage {
    constructor(page) {
        this.page = page;

        // Locators
        this.URL = '';
        this.breadCrumb = '#breadcrumb';
        this.basketSummary = '#basketGrandTotal';
        this.basketUpdateButton = '#basketcontents_table #basketUpdate';
        this.addBasketCouponField = '#input_voucherNr';
        this.addBasketCouponButton = '//div[@id="basketVoucher"]//button';
        this.removeBasketCoupon = '.couponData .removeFn';
    }

    // Dynamic locators that require item position
    basketItemAmount(itemPosition) {
        return `#basketcontents_table #am_${itemPosition}`;
    }

    basketItemTotalPrice(itemPosition) {
        return `//tr[@id="table_cartItem_${itemPosition}"]/td[@class="totalPrice"]`;
    }

    basketItemTitle(itemPosition) {
        return `//tr[@id="table_cartItem_${itemPosition}"]/td[2]/div[2]/a`;
    }

    basketItemId(itemPosition) {
        return `//tr[@id="table_cartItem_${itemPosition}"]/td[2]/div[2]/div[1]`;
    }

    basketBundledItemAmount(itemPosition) {
        return `//tr[@id="table_cartItem_${itemPosition}"]/td[4]`;
    }

    openGiftSelection(itemPosition) {
        return `//tr[@id="table_cartItem_${itemPosition}"]/td[3]/a`;
    }

    /**
     * Update product amount in the basket
     *
     * @param {number} amount
     * @param {number} itemPosition
     * @return {BasketPage} for chaining
     */
    async updateProductAmount(amount, itemPosition = 1) {
        await this.page.fill(this.basketItemAmount(itemPosition), amount.toString());
        await this.page.click(this.basketUpdateButton);
        return this;
    }

    /**
     * Assert basket contains products
     *
     * basketProducts = [
     *   {
     *     id: 'productId',
     *     title: 'productTitle',
     *     amount: 2,
     *     totalPrice: '99.90'
     *   }
     * ]
     *
     * @param {Array} basketProducts
     * @param {string} basketSummaryPrice
     * @return {BasketPage} for chaining
     */
    async seeBasketContains(basketProducts, basketSummaryPrice) {
        for (let i = 0; i < basketProducts.length; i++) {
            const itemPosition = i + 1;
            const { id, title, amount, totalPrice } = basketProducts[i];

            // Check product ID
            await expect(this.page.locator(this.basketItemId(itemPosition)))
                .toContainText(`PRODUCT_NO ${id}`);

            // Check product title
            await expect(this.page.locator(this.basketItemTitle(itemPosition)))
                .toContainText(title);

            // Check total price
            await expect(this.page.locator(this.basketItemTotalPrice(itemPosition)))
                .toContainText(totalPrice);

            // Check amount in input field
            await expect(this.page.locator(this.basketItemAmount(itemPosition)))
                .toHaveValue(amount.toString());
        }

        // Check basket summary price
        await expect(this.page.locator(this.basketSummary)).toContainText(basketSummaryPrice);

        return this;
    }

    /**
     * Assert basket contains a bundled product
     *
     * basketProduct = {
     *   id: 'productId',
     *   title: 'productTitle',
     *   amount: 1
     * }
     *
     * @param {Object} basketProduct
     * @param {number} itemPosition
     * @return {BasketPage} for chaining
     */
    async seeBasketContainsBundledProduct(basketProduct, itemPosition) {
        const { id, title, amount } = basketProduct;

        // Check product ID
        await expect(this.page.locator(this.basketItemId(itemPosition)))
            .toContainText(`PRODUCT_NO ${id}`);

        // Check product title
        await expect(this.page.locator(this.basketItemTitle(itemPosition)))
            .toContainText(title);

        // Check bundled product amount
        await expect(this.page.locator(this.basketBundledItemAmount(itemPosition)))
            .toContainText(amount.toString());

        return this;
    }

    /**
     * Go to the next step in the checkout
     *
     * @return {BasketPage} or a new Page Object for the next step
     */
    async goToNextStep() {
        // Adjust the locator/text if it differs in your template
        await this.page.click('text=CONTINUE_TO_NEXT_STEP');
        await this.page.waitForSelector(this.breadCrumb);
        // return new UserCheckout(this.page); // If you have a UserCheckout page object
        return this;
    }

    /**
     * Add a coupon to the basket
     *
     * @param {string} couponNumber
     * @return {BasketPage} for chaining
     */
    async addCouponToBasket(couponNumber) {
        await this.page.fill(this.addBasketCouponField, couponNumber);
        await this.page.click(this.addBasketCouponButton);
        await this.page.waitForSelector('.couponData', { state: 'visible' });
        return this;
    }

    /**
     * Remove a coupon from the basket
     *
     * @return {BasketPage} for chaining
     */
    async removeCouponFromBasket() {
        await this.page.click(this.removeBasketCoupon);
        return this;
    }

    /**
     * Open the gift selection widget
     *
     * @param {number} itemPosition
     * @return {BasketPage} or a new Page Object for the GiftSelection
     */
    async openGiftSelectionWidget(itemPosition) {
        await this.page.click(this.openGiftSelection(itemPosition));
        await this.page.waitForSelector('text=GIFT_OPTION');
        // return new GiftSelection(this.page); // If you have a GiftSelection page object
        return this;
    }
}
