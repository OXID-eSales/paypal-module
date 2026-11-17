import { test, expect } from '@playwright/test';
import { ShopHelper } from "../../helpers/ShopHelper";
import { PaypalHelper } from "../../helpers/PaypalHelper";
import dotenv from 'dotenv';

dotenv.config();

let context, page;

// every test gets its own browser context: cookies, shop session and PayPal login of the
// previous test made the next one depend on how that one ended
test.beforeEach(async ({ browser }) => {
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

test('Paypal Express Payment method minibasket', async () => {
    const shopHelper = new ShopHelper(page);
    const paypalHelper = new PaypalHelper(page, context);
    await shopHelper.loginUser();
    await shopHelper.addItemsToCart();
    await paypalHelper.clickPaypalButtonInIframe('PaypalExpress');
    await paypalHelper.handlePopup();
    await shopHelper.acceptTerms();
    await shopHelper.orderNow();
    await paypalHelper.verifyThankYouPage();
});

test.skip('Paypal Express Payment method Cart', async () => {
    const shopHelper = new ShopHelper(page);
    const paypalHelper = new PaypalHelper(page, context);
    await shopHelper.loginUser();
    await shopHelper.addItemsToCart();
    await paypalHelper.clickPaypalButtonInIframe('PaypalExpress');
    await paypalHelper.handlePopup();
    await shopHelper.acceptTerms();
    await shopHelper.orderNow();
    await paypalHelper.verifyThankYouPage();
});

test('Paypal Express Payment method Guest', async () => {
    const shopHelper = new ShopHelper(page);
    const paypalHelper = new PaypalHelper(page, context);
    await shopHelper.addItemsToCart();
    await paypalHelper.clickPaypalButtonInIframe('PaypalExpress');
    await paypalHelper.handlePopup();
    await shopHelper.acceptTerms();
    await shopHelper.orderNow();
    await paypalHelper.verifyThankYouPage();
});




test('Paypal Express without accepting T&C', async () => {
    const shopHelper = new ShopHelper(page);
    const paypalHelper = new PaypalHelper(page, context);
    await shopHelper.loginUser();
    await shopHelper.addItemsToCart();
    await paypalHelper.clickPaypalButtonInIframe('PaypalExpress');
    await paypalHelper.handlePopup();
    await shopHelper.orderNow();
    expect(await shopHelper.verifyTCAuth()).toBe(true);
});

test.afterEach(async () => {
    if (context) {
        await context.close().catch(() => {});
    }
});
