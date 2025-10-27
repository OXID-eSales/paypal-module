import { test, expect } from '@playwright/test';
import {ShopHelper} from "../../helpers/ShopHelper";
import {PaypalHelper} from "../../helpers/PaypalHelper";
import dotenv from 'dotenv';

dotenv.config();
// test.skip('ACDC Payment test', () => {
let context, page;

test.beforeAll(async ({ browser }) => {
    const context = await browser.newContext({
        extraHTTPHeaders: {
            'ngrok-skip-browser-warning': 'true',
        },
    });

    await context.clearCookies();

    page = await context.newPage();
    page.setDefaultTimeout(30000);
    page.setDefaultNavigationTimeout(30000);
    await page.goto(process.env.BASE_URL);
});

test.skip('Paypal PUI Month Validation Pass', async () => {
    const shopHelper = new ShopHelper(page);
    await shopHelper.loginUser();
    // Scroll wheel by X:0, Y:1848
    await page.mouse.wheel(0, 1848);

    // Click on <button> "To cart"
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

});
