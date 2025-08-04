import { test, expect } from '@playwright/test';
import { ShopHelper } from "../../helpers/ShopHelper";
import { PaypalHelper } from "../../helpers/PaypalHelper";
import dotenv from 'dotenv';

dotenv.config();

test.describe('Simple PayPal payment test', () => {
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

    test('Should complete PayPal payment end-to-end', async () => {
        test.setTimeout(300000);

        const shopHelper = new ShopHelper(page);
        const paypalHelper = new PaypalHelper(page, context);

        try {
            console.log('Step 1: Setting up the cart...');
            await shopHelper.loginUser();
            await shopHelper.addItemsToCart();
            await shopHelper.checkout(); // This should only be called once
            await shopHelper.selectPaymentMethodPaypal();
            await shopHelper.nextStep();
            await shopHelper.orderNow();

            // Step 2: Handle PayPal button click in iframe and process the popup
            // Step 2: Handle PayPal button click in iframe and process the popup
            console.log('Step 2: Handling PayPal iframe and popup...');
            const paypalIframeSelectors = [
                'iframe[title="PayPal"]',
                'iframe[title*="PayPal"]',
                '.component-frame.visible',
                'iframe[src*="paypal"]'
            ];

            try {
                // Get the popup page
                const popupPage = await shopHelper.clickPaypalButtonAndGetPopup();

                if (popupPage) {
                    await paypalHelper.handlePaypalPopupAndLogin(popupPage);
                    await popupPage.locator('button[data-id="payment-submit-btn"]').getByText('Pay').click();
                    console.log('Waiting for redirect after PayPal payment...');
                    await page.waitForSelector('#thankyouPage, .alert-success', {
                        timeout: 60000,
                        state: 'visible'
                    });

                    console.log('Payment successfully completed!');
                } else {
                    throw new Error('PayPal popup was not detected');
                }
            } catch (error) {
                console.error('PayPal payment failed:', error.message);

                // Take screenshot for debugging
                await page.screenshot({ path: 'paypal-payment-failure.png' });

                throw error;
            }

            // Step 3: Verify the thank you page is displayed
            console.log('Step 3: Verifying thank you page...');
            await page.waitForSelector('#thankyouPage', { timeout: 60000 });

            // The redirect should already be handled by handlePaypalPopup
            // Just verify the thank you page is displayed
            const thankYouText = await page.locator('#thankyouPage').isVisible();
            expect(thankYouText).toBeTruthy();

            console.log('Payment successfully completed and thank you page displayed!');

        } catch (error) {
            console.error('Test failed:', error.message);

            // Only attempt screenshot if page is still available
            try {
                if (page && !page.isClosed()) {
                    await page.screenshot({
                        path: './test-failure.png',
                        fullPage: true
                    }).catch(screenshotError => {
                        console.log('Failed to take screenshot:', screenshotError.message);
                    });
                }
            } catch (screenshotError) {
                console.log('Screenshot attempt failed:', screenshotError.message);
            }

            // Log all open pages for debugging (only if context is available)
            try {
                if (context && context.pages().length > 0) { // Fixed the condition here
                    const allPages = context.pages();
                    console.log(`Total pages open: ${allPages.length}`);
                    allPages.forEach((p, index) => {
                        try {
                            console.log(`Page ${index + 1}: ${p.url()}`);
                        } catch (urlError) {
                            console.log(`Page ${index + 1}: URL not accessible (page may be closed)`);
                        }
                    });
                }
            } catch (contextError) {
                console.log('Context debugging failed:', contextError.message);
            }

            throw error;
        }
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
});
