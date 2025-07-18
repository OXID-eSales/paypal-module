// playwright/global-setup.js
const fs = require('fs');

// This is a proper global setup module for Playwright
module.exports = async () => {
    // Load test data
    const testData = require('testDeata/test-data.json');

    // Make test data available in environment variables
    process.env.PAYPAL_CREDS = JSON.stringify(testData.paypal.sandbox);

    console.log('Global setup complete. PayPal credentials loaded.');

    // You can add authentication setup here if needed
    // For example, log in once and save the state
    /*
    const { chromium } = require('@playwright/test');
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();

    // Navigate to your site
    await page.goto('https://your-site.com/login');

    // Log in
    await page.fill('#email', 'user@example.com');
    await page.fill('#password', 'password123');
    await page.click('#login-button');

    // Save authentication state to reuse in tests
    await context.storageState({ path: 'auth.json' });

    await browser.close();
    */
};
