import { test, expect } from '@playwright/test';
import { ShopHelper } from "../../helpers/ShopHelper";
import dotenv from 'dotenv';
import {PuiHelper} from "../../helpers/PuiHelper";

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
test('PUI Payment method', async () => {
    const shopHelper = new ShopHelper(page);
    const puiHelper = new PuiHelper(page)
    await shopHelper.loginUser();
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await shopHelper.selectPaymentMethod('PayPal Pay upon Invoice');
    await shopHelper.nextStep();
    await puiHelper.fillPuiForm()
    await shopHelper.acceptTerms();
    await shopHelper.orderNow();
    // await paypalHelper.clickPaypalButtonInIframe('Paypal'); // click iframe (2nd if present)
    // await paypalHelper.handlePopup();               // handle popup → return
    await shopHelper.verifyThankYouPage();        // verify success
});

test('PUI without accepting T&C', async () => {
    const shopHelper = new ShopHelper(page);
    const puiHelper = new PuiHelper(page)
    await shopHelper.loginUser();
    await shopHelper.addItemsToCart();
    await shopHelper.checkout();
    await shopHelper.selectPaymentMethod('PayPal Pay upon Invoice');
    await shopHelper.nextStep();
    await puiHelper.fillPuiForm()
    await shopHelper.orderNow();
    await shopHelper.verifyTCAuth()
});

test('Paypal PUI Month Validation Fail', async () => {
    const shopHelper = new ShopHelper(page);
    await shopHelper.loginUser();
    await page.mouse.wheel(0, 1848);
    await Promise.all([
        page.click('#submitnewItems_3'),
        page.waitForNavigation()
    ]);

    // Click on <svg> .btn-minibasket:nth-child(4) > svg
    await page.click('.btn-minibasket:nth-child(4) > svg');

    // Click on <a> "Checkout"
    await Promise.all([
        page.click('[href="https://osc4.oxid.shop/index.php?lang=1&cl=payment"]'),
        page.waitForNavigation()
    ]);

    // Click on <input> #payment_oscpaypal_pui
    await page.click('#payment_oscpaypal_pui');

    // Click on <button> "Next"
    await Promise.all([
        page.click('.btn-highlight:nth-child(2)'),
        page.waitForNavigation()
    ]);

    // Click on <button> "Order now"
    await page.click('.btn-highlight:nth-child(3)');

    // Click on <div> "Please specify a value fo..."
    await page.click('.pui_required_birthdate_day_help > .text-danger');

    // Click on <div> "Please specify a value fo..."
    await page.click('.pui_required_phonenumber_help > .text-danger');

    // Click on <input> #pui_required_birthdate_day
    await page.click('#pui_required_birthdate_day');

    // Fill "22" on <input> #pui_required_birthdate_day
    await page.fill('#pui_required_birthdate_day', "22");

    // Click on <input> #pui_required_birthdate_year
    await page.click('#pui_required_birthdate_year');

    // Fill "1998" on <input> #pui_required_birthdate_year
    await page.fill('#pui_required_birthdate_year', "1998");

    // Click on <input> #pui_required_phonenumber
    await page.click('#pui_required_phonenumber');

    // Fill "+4930123456789" on <input> #pui_required_phonenumber
    await page.fill('#pui_required_phonenumber', "+4930123456789");

    // Click on <button> "Order now"
    await page.click('.btn-highlight:nth-child(3)');

    await page.waitForTimeout(2000); // 2000 ms = 2 seconds
    // Click on <button> "Order now"
    await page.click('.btn-highlight:nth-child(3)');
    await page.waitForTimeout(2000); // 2000 ms = 2 seconds
    await page.click('.btn-highlight:nth-child(3)');
    await page.waitForTimeout(2000); // 2000 ms = 2 seconds

// Locate the element
    const element = page.locator('.pui_required_birthdate_day_help > .text-danger');

// Check that the element no longer has the 'visually-hidden' class (it should be visible now)
    await expect(element).not.toHaveClass(/visually-hidden/);

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
