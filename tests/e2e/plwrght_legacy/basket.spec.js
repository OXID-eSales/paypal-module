// basket.spec.js
import { test } from '@playwright/test';
import { BasketPage } from '../PageObjects/Checkout/BasketPage.js';

require('dotenv').config();

test('Basket workflow', async ({ page }) => {

    const basket = new BasketPage(page);
    console.log('Base URL:', process.env.BASE_URL);

    // Go to basket page
    await page.goto(process.env.BASE_URL + '/basket');

    // Update product amount
    await basket.updateProductAmount(2, 1);

    // Check if basket has expected product
    await basket.seeBasketContains(
        [
            {
                id: '12345',
                title: 'Sample Product',
                amount: 2,
                totalPrice: '19.99'
            }
        ],
        'Total: 19.99' // Example summary price
    );

    // Add coupon
    await basket.addCouponToBasket('COUPON2025');

    // Remove coupon
    await basket.removeCouponFromBasket();

    // Move to next step
    await basket.goToNextStep();
});
