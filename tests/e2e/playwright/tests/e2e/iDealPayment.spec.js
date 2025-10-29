import { test, expect } from '@playwright/test';
import { ShopHelper } from "../../helpers/ShopHelper";
import dotenv from 'dotenv';
import { UAPMHelper } from "../../helpers/UAPMHelper";

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

test('iDEAL Successfull Payment method', async () => {
    const shopHelper = new ShopHelper(page);
    const UAPMHelper = new UAPMHelper(page);

    // Step 1: Login, select country, and add items
    await shopHelper.loginUser();
    await shopHelper.changeCountry('Netherlands');
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await shopHelper.selectPaymentMethod('PayPal iDEAL');
    await shopHelper.nextStep();
    await shopHelper.acceptTerms();
await shopHelper.orderNow('1')
    await UAPMHelper.checkPaymentRedirect(page)
    await UAPMHelper.clickTestSuccessfulPayment(); // Test for successful payment
    await shopHelper.verifyThankYouPage(); // Verify success
});

test('iDEAL Failed Payment method', async () => {
    const shopHelper = new ShopHelper(page);
    const UAPMHelper = new UAPMHelper(page);
    // Step 1: Login, select country, and add items
    await shopHelper.loginUser();
    await shopHelper.changeCountry('Netherlands');
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await page.waitForTimeout(10000);
    await shopHelper.selectPaymentMethod('PayPal iDEAL');
    await shopHelper.nextStep();
    await shopHelper.acceptTerms();
    await shopHelper.orderNow('1')
    await UAPMHelper.checkPaymentRedirect(page)
    await page.waitForTimeout(10000);
    await uAPMHelper.clickTestFailedPayment()
    await uAPMHelper.verifyFailed();
    await page.waitForTimeout(10000);

    await uAPMHelper.verifyAuthorizationFailed();
});

test('iDEAL Cancelled Payment method', async () => {
    const shopHelper = new ShopHelper(page);
    const UAPMHelper = new UAPMHelper(page);
    await shopHelper.loginUser();
    await shopHelper.changeCountry('Netherlands');
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await page.waitForTimeout(10000);
    await shopHelper.selectPaymentMethod('PayPal iDEAL');
    await shopHelper.nextStep();
    await shopHelper.acceptTerms();
    await shopHelper.orderNow('1')
    await UAPMHelper.checkPaymentRedirect(page)
    await page.waitForTimeout(10000);
    await uAPMHelper.clickTestCancelledPayment(); // Test for successful payment
    await uAPMHelper.verifyCanceled();
    await page.waitForTimeout(10000);
    await uAPMHelper.verifyAuthorizationFailed();
})

test('iDEAL without accepting T&C', async () => {
    const shopHelper = new ShopHelper(page);
    await shopHelper.loginUser();
    await shopHelper.changeCountry('Netherlands');
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await shopHelper.selectPaymentMethod('PayPal iDEAL');
    await shopHelper.nextStep();
    await shopHelper.orderNow('1')
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
