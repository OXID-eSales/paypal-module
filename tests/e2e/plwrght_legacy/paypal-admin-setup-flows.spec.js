// playwright/paypal-flows.spec.js
import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
const testData = require('../../testData/test-data.json');
require('dotenv').config();

// Ensure the _generated/screenshots directory exists
const screenshotDir = path.resolve(__dirname, '../_generated/screenshots');
if (!fs.existsSync(screenshotDir)) {
    fs.mkdirSync(screenshotDir, { recursive: true });
}

// This gets the PayPal credentials from environment variables set in the config
let paypalCreds = {
    email: process.env.PAYPAL_EMAIL,
    password: process.env.PAYPAL_PASSWORD,
};

const BASE_URL = process.env.BASE_URL || 'http://localhost'; // Use "apache" inside Docker


test.describe('PayPal Checkout Flows', () => {
    test.beforeEach(async ({ page }) => {
        console.log('Navigating to product page... ' + BASE_URL);
        try {
            await page.goto(BASE_URL + '/admin', { timeout: 5000 });
        } catch (error) {
            if (error.message.includes('net::ERR_NAME_NOT_RESOLVED')) {
                console.error('❌ DNS resolution failed! Failing the test immediately.');
                throw new Error('DNS resolution failed. Url ' + BASE_URL + ' is not reachable.');
            }
            throw error; // Re-throw other errors
        }

        await page.fill('#usr', testData['admin']['login']);
        await page.fill('#pwd', testData['admin']['password']);
        await page.click('input[type="submit"].btn');
        await shot('loggedin.png', page);
    });

    test('Setup Paypal Admin Configuration', async ({ page, context }) => {
        const navigationFrame = page.frame({ name: 'navigation' });
        if (!navigationFrame) {
            throw new Error('Navigation frame "navigation" not found!');
        }

        const adminNavFrame = navigationFrame.childFrames().find(frame => frame.name() === 'adminnav');
        if (!adminNavFrame) {
            throw new Error('Nested frame "adminnav" was not found inside the navigation frame!');
        }

        const paypalLocator = adminNavFrame.locator('a:has-text("PayPal")');
        await paypalLocator.click();
        await paypalLocator.click();

        // Use a more specific selector for the Configuration link within the submenu.
        const configLocator = adminNavFrame.locator('li#nav-1-9-1 > a.rc:has-text("Configuration")');

        // Wait for the Configuration link to be visible and then click it.
        await configLocator.waitFor({ state: 'visible', timeout: 15000 });
        await configLocator.click();


    });
});

async function shot(name, page) {
    const screenshotPath = path.join(screenshotDir, name);
    await page.screenshot({ path: screenshotPath });
}