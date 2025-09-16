import { expect } from "@playwright/test";

export class CreditCardHelper {
    constructor(page, context) {
        if (!page || !context) {
            throw new Error('Invalid or undefined "page" or "context" provided to CreditCardHelper.');
        }

        this.page = page;
        this.context = context;
    }

    async addNewCard(selectors) {
        const { page } = this; // Access page from the instance variable

        // 1) Wait for the card container to be visible
        await expect(page.locator('#card_container')).toBeVisible();

        // 2) Card Number
        const numberFrame = page.frameLocator('#card-number-field-container iframe[title="paypal_card_number_field"]');
        const numberInput = numberFrame.locator('input, [contenteditable="true"]').first();
        await expect(numberInput).toBeVisible();
        await numberInput.fill('4020 0201 0112 3947');

        // 3) Expiry
        const expiryFrame = page.frameLocator('#card-expiry-field-container iframe[title="paypal_card_expiry_field"]');
        const expiryInput = expiryFrame.locator('input, [contenteditable="true"]').first();
        await expect(expiryInput).toBeVisible();
        await expiryInput.fill('03 / 33');

        // 4) CVV
        const cvvFrame = page.frameLocator('#card-cvv-field-container iframe[title="paypal_card_cvv_field"]');
        const cvvInput = cvvFrame.locator('input, [contenteditable="true"]').first();
        await expect(cvvInput).toBeVisible();
        await cvvInput.fill('123');


    }
}
