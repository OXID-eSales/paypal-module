import { test, expect } from '@playwright/test';
import { ShopHelper } from "../../helpers/ShopHelper";
import { PaypalHelper } from "../../helpers/PaypalHelper";
import dotenv from 'dotenv';

dotenv.config();

    let context, page;

    test.beforeAll(async ({ browser }) => {
        context = await browser.newContext({
            extraHTTPHeaders: {
                'ngrok-skip-browser-warning': 'true',
            },
        });

        await context.clearCookies();
        page = await context.newPage();
        page.setDefaultTimeout(45000); // Increased timeout
        page.setDefaultNavigationTimeout(45000); // Increased timeout
        await page.goto(process.env.BASE_URL);
    });
test('Paypal Payment method', async () => {
    const shopHelper = new ShopHelper(page);
    const paypalHelper = new PaypalHelper(page, context);

    await shopHelper.loginUser();
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await shopHelper.selectPaymentMethod('PayPal');
    await shopHelper.nextStep();
    await shopHelper.acceptTerms();
    await shopHelper.orderNow();
    await paypalHelper.clickPaypalButtonInIframe('Paypal'); // click iframe (2nd if present)
    await paypalHelper.handlePopup();               // handle popup → return
    await paypalHelper.verifyThankYouPage();        // verify success
});

test('Paypal Payment method Without Accepting T&C', async () => {
    const shopHelper = new ShopHelper(page);
    const paypalHelper = new PaypalHelper(page, context);

    await shopHelper.loginUser();
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await shopHelper.selectPaymentMethod('PayPal');
    await shopHelper.nextStep();
    await page.waitForTimeout(3000);
    await shopHelper.orderNow();
    await page.waitForTimeout(3000);
    await shopHelper.verifyTCAuth()
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
