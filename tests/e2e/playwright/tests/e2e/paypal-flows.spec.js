// playwright/paypal-flows.spec.js
import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
const testData = require('../../testData/test-data.json');
require('dotenv').config();

// Ensure the _generated/screenshots directory exists
const screenshotDir = path.resolve(__dirname, '../../_generated/screenshots');
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
            await page.goto(BASE_URL + '/Bekleidung/Fashion/Accessoires/Kuyichi-Lederguertel-JEVER.html', { timeout: 5000 });
        } catch (error) {
            if (error.message.includes('net::ERR_NAME_NOT_RESOLVED')) {
                console.error('❌ DNS resolution failed! Failing the test immediately.');
                throw new Error('DNS resolution failed. Url ' + BASE_URL + ' is not reachable.');
            }
            throw error; // Re-throw other errors
        }
    });

    test('Cancel PayPal checkout flow', async ({ page, context }) => {
        console.log('Starting PayPal cancellation test');
        await page.screenshot({ path: path.join(screenshotDir, 'initial-page.png') });
        const paypalIframeSelectors = [
            'iframe[title="PayPal"]',
            'iframe[title*="PayPal"]'
        ];

        let paypalFrameSelector = await getPaypalFrameSelector(page, paypalIframeSelectors, context);

        if (!paypalFrameSelector) {
            throw new Error('PayPal iframe not found');
        }

        console.log('Attempting to click PayPal button inside iframe...');
        const initialPages = context.pages();
        console.log(`Initial page count: ${initialPages.length}`);

        try {
            await page.waitForTimeout(3000);
            const clicked = await page.evaluate((selector) => {
                const iframe = document.querySelector(selector);
                if (!iframe || !iframe.contentDocument) return false;
                const buttons = iframe.contentDocument
                    .querySelectorAll('[data-funding-source="paypal"], [role="button"], .paypal-button, button');
                console.log('Found buttons in iframe:', buttons.length);

                if (buttons.length > 0) {
                    // Click the first button
                    buttons[0].click();
                    return true;
                }
                return false;
            }, paypalFrameSelector);

            console.log('JavaScript click result:', clicked);

            // Instead of waiting for popup event, look for new pages manually
            console.log('Waiting to check for new pages...');
            await page.waitForTimeout(5000); // Give it time to open new pages

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

                console.log('Popup URL:', popupPage.url());
                await popupPage.screenshot({ path: path.join(screenshotDir, 'paypal-popup.png'), fullPage: true });

                // Handle the PayPal popup
                await handlePayPalPopup(popupPage, page);
                await paypalExressIsStillVisibleAndFuncitonable(page, context);
            } else {
                console.log('No new pages detected after clicking PayPal button');

                // Check if we were redirected to PayPal in the main window
                console.log('Current main page URL:', page.url());
                if (page.url().includes('paypal.com')) {
                    console.log('Redirected to PayPal in main window');
                    await handlePayPalInMainWindow(page);
                } else {
                    console.log('No redirection or popup detected');




                    // Wait a bit longer and try again
                    console.log('Waiting longer to see if redirection happens...');
                    await page.waitForTimeout(5000);

                    // Check again
                    if (page.url().includes('paypal.com')) {
                        console.log('Delayed redirect to PayPal detected');
                        await handlePayPalInMainWindow(page);
                        await paypalExressIsStillVisibleAndFuncitonable(page, context);
                    } else {
                        // If still nothing, check for new popups again
                        const latePages = context.pages();
                        const lateNewPages = latePages.filter(p => !initialPages.includes(p));

                        if (lateNewPages.length > 0) {
                            console.log('Late popup detected');
                            const latePopupPage = lateNewPages[0];
                            await handlePayPalPopup(latePopupPage, page);
                        } else {
                            throw new Error('PayPal button clicked but no popup or redirect occurred');
                        }
                    }
                }
            }
        } catch (error) {
            console.log('Error during PayPal button interaction:', error.message);

            // List all pages in context to help diagnose
            const finalPages = context.pages();
            console.log(`Final page count: ${finalPages.length}`);
            for (let i = 0; i < finalPages.length; i++) {
                console.log(`Page ${i + 1} URL: ${finalPages[i].url()}`);
            }

            throw error;
        }
    });
});

// Helper function to handle PayPal popup
async function handlePayPalPopup(popupPage, mainPage) {
    console.log('Handling PayPal in popup window');

    try {
        // Find and click the cancel link/button
        const cancelSelectors = [
            '#cancelLink',
            'a[href*="cancel"]',
            'button:has-text("Cancel")',
            'a:has-text("Cancel")',
            '.cancelUrl',
            '#cancel_return',
            '[data-testid="cancel-button"]'
        ];

        let cancelFound = false;
        for (const selector of cancelSelectors) {
            console.log(`Looking for cancel with selector: ${selector}`);
            const count = await popupPage.locator(selector).count();
            if (count > 0) {
                console.log(`Found cancel element with selector: ${selector}`);
                await popupPage.locator(selector).first().click();
                cancelFound = true;
                break;
            }
        }

        console.log('Checking for email field to enter username');
        const emailField = popupPage.locator('#email');
        if (await emailField.count() > 0) {
            // Get email from environment variables
            const email = paypalCreds['email'];
            console.log(`Entering username ${email} to #email input`);
            await emailField.fill(email);
            await popupPage.waitForTimeout(5000);
            console.log('Email entered successfully');
        }

        const nextButton = popupPage.locator('#btnNext');
        if (await nextButton.count() > 0) {
            console.log('Submit button found, clicking...');
            await nextButton.click();
            console.log('Submit button clicked');
        } else {
            console.error('Submit button not found, cannot proceed');
        }

        await popupPage.waitForTimeout(5000);

        console.log('Checking for password field to enter username');
        const passwordField = popupPage.locator('#password');
        if (await passwordField.count() > 0) {
            console.log('Password entered successfully:' + paypalCreds['password']);
            await passwordField.fill(paypalCreds['password']);
        }

        const buttonLogin = popupPage.locator('button[id="btnLogin"]');
        await buttonLogin.waitFor({ state: 'visible' }); // Wait until the button is visible
        await buttonLogin.click(); // Click the button
        console.log("Logged into Paypal");

        const continueButton = popupPage.locator('button:has-text("Continue to review")');
        await continueButton.waitFor({ state: 'visible' }); // Wait for it to become visible
        console.log("The 'Continue to review' button is visible.");

        // Close the popup
        await popupPage.close();
        console.log("Popup closed successfully.");
    } catch (error) {
        console.log('Error handling PayPal popup:', error.message);
        throw error;
    }
}

// Helper function to handle PayPal in the main window
async function handlePayPalInMainWindow(page) {
    console.log('Handling PayPal in main window');

    try {
        // Find and click the cancel link/button
        const cancelSelectors = [
            '#cancelLink',
            'a[href*="cancel"]',
            'button:has-text("Cancel")',
            'a:has-text("Cancel")',
            '.cancelUrl',
            '#cancel_return',
            '[data-testid="cancel-button"]'
        ];

        let cancelFound = false;
        for (const selector of cancelSelectors) {
            console.log(`Looking for cancel element with selector: ${selector}`);
            const count = await page.locator(selector).count();
            if (count > 0) {
                console.log(`Found cancel element with selector: ${selector}`);
                await page.locator(selector).first().click();
                cancelFound = true;
                break;
            }
        }

        if (!cancelFound) {
            console.log('No cancel button found, trying browser back button');
            await page.goBack();
        }

        // Wait for the page to load after cancellation
        await page.waitForTimeout(5000);
        await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(e => {
            console.log('Error waiting for page load:', e.message);
        });

        // Log the final URL
        console.log('Final URL after cancellation:', page.url());

        // Test passed
        console.log('PayPal cancellation test completed successfully');
    } catch (error) {
        console.log('Error handling PayPal in main window:', error.message);
        throw error;
    }
}

async function paypalExressIsStillVisibleAndFuncitonable(page, context) {
    console.log('Verifying PayPal Express buttons');
    const paypalIframeSelectors = [
        'iframe[title="PayPal"]',
        'iframe[title*="PayPal"]'
    ];

    const paypalButtonsSelector = await getPaypalFrameSelector(page, paypalIframeSelectors);

    if (!paypalButtonsSelector) {
        throw new Error('PayPal iframe not found');
    }

    // Check if buttons are present on the page
    const paypalButtons = page.locator(paypalButtonsSelector);
    const buttonCount = await paypalButtons.count();
    if (buttonCount === 0) {
        throw new Error('PayPal Express buttons not found on the page');
    }
    console.log(`Found ${buttonCount} PayPal Express buttons on the page`);

    // Iterate through buttons and perform checks
    for (let i = 0; i < buttonCount; i++) {
        console.log(`Checking PayPal button #${i + 1}`);

        // Check if the button is visible
        const button = paypalButtons.nth(i);
        if (!(await button.isVisible())) {
            throw new Error(`PayPal button #${i + 1} is not visible`);
        }
        console.log(`PayPal button #${i + 1} is visible`);

        // Check if the button is enabled (clickable)
        if (!(await button.isEnabled())) {
            throw new Error(`PayPal button #${i + 1} is not enabled`);
        }
        console.log(`PayPal button #${i + 1} is enabled`);
    }

    console.log('PayPal Express buttons verified successfully');
}

async function getPaypalFrameSelector(page, paypalIframeSelectors) {
    // Try different iframe selectors
    let paypalFrameSelector = null;
    for (const selector of paypalIframeSelectors) {
        console.log(`Trying iframe selector: ${selector}`);
        const frameCount = await page.locator(selector).count();
        if (frameCount > 0) {
            console.log(`Found ${frameCount} frames with selector: ${selector}`);
            paypalFrameSelector = selector;
            break;
        }
    }

    return paypalFrameSelector;
}
