import { test, expect } from '@playwright/test';
import { ShopHelper } from "../../helpers/ShopHelper";
import dotenv from 'dotenv';
import { UAPMHelper } from "../../helpers/UAPMHelper";

dotenv.config();

let context, page;

// every test gets its own browser context, see PaypalPayment.spec.js
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

test('P24 Successfull Payment method', async () => {
    const shopHelper = new ShopHelper(page);
    const uAPMHelper = new UAPMHelper(page);
    await shopHelper.loginUser();
    await shopHelper.changeCountry('Poland');
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await shopHelper.selectPaymentMethod('Paypal P24');
    await shopHelper.nextStep();
    await shopHelper.acceptTerms();
    await shopHelper.orderNow('1')
    await uAPMHelper.checkPaymentRedirect(page)
    await uAPMHelper.clickTestSuccessfulPayment(); // Test for successful payment
    await shopHelper.verifyThankYouPage(); // Verify success
});

test('P24 Failed Payment method', async () => {
    const shopHelper = new ShopHelper(page);
    const uAPMHelper = new UAPMHelper(page);
    await shopHelper.loginUser();
    await shopHelper.changeCountry('Poland');
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await page.waitForTimeout(10000);
    await shopHelper.selectPaymentMethod('Paypal P24');
    await shopHelper.nextStep();
    await shopHelper.acceptTerms();
    await shopHelper.orderNow('1')
    await uAPMHelper.checkPaymentRedirect(page)
    await page.waitForTimeout(10000);
    await uAPMHelper.clickTestFailedPayment()
    await uAPMHelper.verifyFailed();
    await page.waitForTimeout(10000);
    await uAPMHelper.verifyAuthorizationFailed();
});

test('P24 Cancelled Payment method', async () => {
    const shopHelper = new ShopHelper(page);
    const uAPMHelper = new UAPMHelper(page);
    await shopHelper.loginUser();
    await shopHelper.changeCountry('Poland');
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await page.waitForTimeout(10000)
    await shopHelper.selectPaymentMethod('Paypal P24');
    await shopHelper.nextStep();
    await shopHelper.acceptTerms();
    await shopHelper.orderNow('1')
    await uAPMHelper.checkPaymentRedirect(page)
    await page.waitForTimeout(10000);
    await uAPMHelper.clickTestCancelledPayment(); // Test for successful payment
    await uAPMHelper.verifyCanceled();
    await page.waitForTimeout(10000);
    await uAPMHelper.verifyAuthorizationFailed();
})

test('P24 Without accepting T&C', async () => {
    const shopHelper = new ShopHelper(page);
    const uAPMHelper = new UAPMHelper(page);
    await shopHelper.loginUser();
    await shopHelper.changeCountry('Poland');
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await shopHelper.selectPaymentMethod('Paypal P24');
    await shopHelper.nextStep();
    await shopHelper.orderNow('1')
    await page.waitForTimeout(3000);
    await shopHelper.verifyTCAuth()
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
