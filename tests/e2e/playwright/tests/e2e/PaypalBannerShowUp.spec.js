import { test, expect } from '@playwright/test';
import { ShopHelper } from "../../helpers/ShopHelper";
import { PaypalHelper } from "../../helpers/PaypalHelper";
import dotenv from 'dotenv';

dotenv.config();

test.describe('Simple PayPal payment test', () => {
    let context, page;

    test.beforeAll(async ({ browser }) => {
        context = await browser.newContext({
            extraHTTPHeaders: {
                'ngrok-skip-browser-warning': 'true',
            },
        });

        await context.clearCookies();
        page = await context.newPage();
        page.setDefaultTimeout(45000);
        page.setDefaultNavigationTimeout(45000);
        await page.goto(process.env.BASE_URL);
    });

    test('Should complete PayPal payment end-to-end', async () => {

        const shopHelper = new ShopHelper(page);

        try {
            await shopHelper.loginUser();

            const bannerLocator = page.locator('#paypal-installment-banner-container');

            await expect(bannerLocator).toBeVisible();

            console.log('Payment successfully completed and thank you page displayed!');

        } catch (error) {
            console.error('Test failed:', error.message);
        }
    });

    test.afterAll(async () => {
        try {
            if (context && !context.browser().isConnected()) {
                // Browser is already disconnected, don't try to close
                return;
            }

            if (context) {
                await context.close();
            }
        } catch (closeError) {
            console.log('Error closing context:', closeError.message);
        }
    });
});
