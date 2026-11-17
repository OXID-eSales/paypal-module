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
test('Paypal Payment method', async () => {
    const shopHelper = new ShopHelper(page);
    const paypalHelper = new PaypalHelper(page, context);

    await shopHelper.loginUser();
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await shopHelper.selectPaymentMethod('PayPal');
    await shopHelper.nextStep();
    await shopHelper.acceptTerms();
    // popup flow: the PayPal button takes the place of "Order now" on the order page
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
    // without confirmed terms the module refuses the click (onClick -> actions.reject()) and shows
    // an error message; the SDK may open its popup before that and closes it again
    const popup = await paypalHelper.clickPaypalButtonInIframe('Paypal').catch(() => null);
    if (popup && !popup.isClosed()) {
        await popup.waitForEvent('close', { timeout: 15000 }).catch(() => {});
    }
    expect(popup === null || popup.isClosed()).toBe(true);
    expect(await shopHelper.verifyTCAuth()).toBe(true);
});

test.afterEach(async () => {
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
