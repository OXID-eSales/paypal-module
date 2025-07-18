import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
const testData = require('../../testData/test-data.json');
require('dotenv').config();

if (!testData['admin'] || !testData['admin'].email || !testData['admin'].password) {
    throw new Error('Admin credentials are missing in test data!');
}

test.describe('Admin Setup', () => {

    let page;
    let context;

    test.beforeAll(async ({ browser }) => {
        context = await browser.newContext();
        page = await context.newPage();
    });

    test('Paypal Admin Setup', async () => {
        console.log('Navigating to product page... ' + process.env.BASE_URL);
        try {
            await page.goto(process.env.BASE_URL + '/admin', { timeout: 5000 });
        } catch (error) {
            if (error.message.includes('net::ERR_NAME_NOT_RESOLVED')) {
                console.error('❌ DNS resolution failed! Failing the test immediately.');
                throw new Error('DNS resolution failed. Url ' + process.env.BASE_URL + ' is not reachable.');
            }
            throw error;
        }

        const adminEmail = testData['admin'].email;
        const adminPassword = testData['admin'].password;

        await page.fill('#usr', adminEmail);
        await page.fill('#pwd', adminPassword);
        await page.click('input[type="submit"].btn');

        // Capture screenshot after successful login
        await page.screenshot({ path: 'loggedin.png' });
        await page.waitForTimeout(2000);

        const navigationFrame = await page.frame({ name: 'navigation' });
        if (!navigationFrame) {
            throw new Error('Navigation frame "navigation" not found!');
        }

        // Log all available frames to verify their names
        page.frames().forEach(frame => {
            console.log('Frame name:', frame.name());
        });

        // Wait for the 'adminnav' frame inside the 'navigation' frame to be available
        const adminNavFrame = navigationFrame.childFrames().find(frame => frame.name() === 'adminnav');
        if (!adminNavFrame) {
            throw new Error('Nested frame "adminnav" was not found inside the navigation frame!');
        }

        // Now, locate and click the PayPal link
        const paypalLocator = adminNavFrame.locator('a:has-text("PayPal")');
        await paypalLocator.waitFor({ state: 'visible', timeout: 5000 });
        await paypalLocator.click();
        const configLocator = adminNavFrame.locator('li#nav-1-9-1 > a.rc:has-text("Configuration")');

        // Wait for the Configuration link to be visible and then click it.
        await configLocator.waitFor({ state: 'visible', timeout: 5000 });
        await configLocator.click();

        await page.waitForTimeout(2000);
        // Locate the iframe by its name
        const iframe = page.frame({ name: 'basefrm' }); // Ensure the name matches the iframe

        if (!iframe) {
            throw new Error('Iframe "basefrm" not found!');
        }

        // Locate the select element with ID 'opmode' inside the iframe
        const opmodeSelect = iframe.locator('#opmode');

        // Select the "sandbox" option
        await opmodeSelect.selectOption({ value: 'sandbox' });

        const initialPages = context.pages();
        console.log(`Initial page count: ${initialPages.length}`);
        // Perform the action that should open the new tab
        const signUpButton = iframe.locator('a.popuplink'); // Adjust the selector
        await signUpButton.waitFor({ state: 'visible', timeout: 15000 });
        await signUpButton.click();



        // Check for new pages
        const afterClickPages = context.pages();
        console.log(`Page count after click: ${afterClickPages.length}`);

        // Find new pages that weren't there before
        const newPages = afterClickPages.filter(p => !initialPages.includes(p));
        console.log(`New pages detected: ${newPages.length}`);

        if (newPages.length > 0) {
            // Found a popup/new page
            const popupPage = newPages[0];
            console.log('New page/popup detected');

            // Wait for the page to finish loading
            await popupPage.waitForLoadState('domcontentloaded').catch(e => {
                console.log('Error waiting for popup page load:', e.message);
            });
        }



        await page.waitForTimeout(2000);

        // / Get all open pages in the browser context;
        const allPages = context.pages();

        // Log the URLs of all open pages
        allPages.forEach((page, index) => {
            console.log(`Page ${index + 1}: ${page.url()}`);
        });

        // Assume you want to interact with the second tab (adjust as needed)
        const targetPage = allPages[1];  // Adjust index to match the desired tab

        // Switch to the target tab and perform actions
        await targetPage.bringToFront();
        await targetPage.waitForTimeout(2000);

        await targetPage.getByTestId('menu-button').click();
        await targetPage.waitForTimeout(2000);
        // await targetPage.getByRole('link', { name: 'Registrieren' }).click();
        // await targetPage.waitForTimeout(2000);

        // await targetPage.getByRole('link', { name: 'Geschäftskonto eröffnen' }).click();

        // await targetPage.getByRole('link', { name: 'Einloggen' }).click();

        await targetPage.waitForTimeout(2000);
        // await targetPage.getByRole('link', { name: 'Einloggen' }).click();

        // await targetPage.waitForTimeout(5000);
        // await targetPage.getByRole('textbox', { name: 'E-Mail-Adresse oder' }).fill('sb-llvgr1009479@business.example.com');
        // await targetPage.getByRole('button', { name: 'Weiter' }).click();

        // await targetPage.waitForTimeout(2000);
        // await targetPage.getByRole('textbox', { name: 'Passwort' }).fill('XyT+6-/U');
        // await targetPage.getByRole('button', { name: 'Einloggen' }).click();
        // await targetPage.waitForTimeout(5000);
    });

    test.afterAll(async () => {
        await context.close();
    });
});
