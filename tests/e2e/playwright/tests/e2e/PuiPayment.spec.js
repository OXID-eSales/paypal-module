import { test, expect } from '@playwright/test';
import { ShopHelper } from "../../helpers/ShopHelper";
import dotenv from 'dotenv';
import {PuiHelper} from "../../helpers/PuiHelper";

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
test('PUI Payment method', async () => {
    const shopHelper = new ShopHelper(page);
    const puiHelper = new PuiHelper(page)
    await shopHelper.loginUser();
    // Pay upon Invoice is for German customers only; other specs move the customer abroad
    await shopHelper.changeCountry('Germany');
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await shopHelper.selectPaymentMethod('PayPal Pay upon Invoice');
    await shopHelper.nextStep();
    await puiHelper.fillPuiForm()
    await shopHelper.acceptTerms();
    await shopHelper.orderNow();
    await shopHelper.verifyThankYouPage();        // verify success
});

test('PUI without accepting T&C', async () => {
    const shopHelper = new ShopHelper(page);
    const puiHelper = new PuiHelper(page)
    await shopHelper.loginUser();
    await shopHelper.changeCountry('Germany');
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await shopHelper.selectPaymentMethod('PayPal Pay upon Invoice');
    await shopHelper.nextStep();
    await puiHelper.fillPuiForm()
    await shopHelper.orderNow();
    expect(await shopHelper.verifyTCAuth()).toBe(true);
});

// Until now a recording against an OXID 7 shop (Apex selectors, its URL); rewritten for Flow:
// without birth date the order is refused and the birth date asks for a value.
test('Paypal PUI Month Validation Fail', async () => {
    const shopHelper = new ShopHelper(page);
    await shopHelper.loginUser();
    await shopHelper.changeCountry('Germany');
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await shopHelper.selectPaymentMethod('PayPal Pay upon Invoice');
    await shopHelper.nextStep();
    // the customer's birth date is prefilled from the account: remove it
    await page.fill('#pui_required_birthdate_day', '');
    await page.fill('#pui_required_birthdate_year', '');
    await shopHelper.acceptTerms();
    await shopHelper.orderNow();
    const birthdateHelp = page.locator('.pui_required_birthdate_day_help > .text-danger').first();
    await expect(birthdateHelp).not.toHaveClass(/hidden/, { timeout: 15000 });
    await expect(page).toHaveURL(/cl=order|order/);
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
