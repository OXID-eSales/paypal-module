export class PaypalHelper {
    constructor(page, context) {
        if (!page || !context) {
            throw new Error('Invalid or undefined "page" or "context" provided to PaypalHelper.');
        }

        this.page = page;
        this.context = context;
        this.paypalCreds = {
            email: process.env.PAYPAL_EMAIL,
            password: process.env.PAYPAL_PASSWORD,
        };
    }

    async clickPaypalButtonInIframe(paypalIframeSelectors) {
        const { page, context } = this;

        const paypalFrameSelector = await this.getPaypalFrameSelector(page, paypalIframeSelectors);
        if (!paypalFrameSelector) throw new Error('PayPal iframe not found');

        console.log('Setting up popup listener...');

        // Set up popup listener BEFORE clicking the button
        const popupPromise = new Promise((resolve, reject) => {
            const timeout = setTimeout(() => {
                reject(new Error('Popup timeout - no popup detected within 20 seconds'));
            }, 20000);

            page.on('popup', async (popup) => {
                clearTimeout(timeout);
                console.log('Popup detected via event listener!');
                console.log('Popup URL:', popup.url());
                resolve(popup);
            });
        });

        // Click the PayPal button inside the iframe
        console.log('Clicking PayPal button in iframe...');

        const clicked = await page.evaluate((selector) => {
            const iframe = document.querySelector(selector);
            if (!iframe?.contentDocument) return false;

            const buttons = iframe.contentDocument.querySelectorAll('[data-funding-source="paypal"], [role="button"], .paypal-button, button');
            console.log('Found buttons in iframe:', buttons.length);

            if (buttons.length > 0) {
                buttons[0].click();
                return true;
            }
            return false;
        }, paypalFrameSelector);

        if (!clicked) throw new Error('PayPal button click failed inside iframe');
        console.log('PayPal button clicked successfully.');

        // Wait for the popup
        try {
            const popupPage = await popupPromise;
            return popupPage;
        } catch (error) {
            console.error('Failed to detect popup:', error.message);

            // Fallback: check for new pages manually
            console.log('Attempting manual popup detection...');
            await page.waitForTimeout(5000);

            const allPages = context.pages();
            const newPages = allPages.filter(p => p !== page);

            if (newPages.length > 0) {
                console.log('Found popup via manual detection');
                return newPages[newPages.length - 1]; // Return the most recent page
            }

            throw new Error('No popup detected via any method');
        }
    }

    async handlePaypalPopup(popupPage, loginCallback) {
        if (!popupPage) throw new Error('No popup page available');

        console.log('Handling PayPal popup...');

        try {
            // Wait for the popup to load
            await popupPage.waitForLoadState('domcontentloaded', { timeout: 20000 });
            console.log('Popup loaded successfully');

            // Execute the login callback
            await loginCallback(popupPage);

            // Wait for the popup to close automatically with extended timeout for slow connections
            console.log('Waiting for popup to close (allowing up to 2 minutes for slow connections)...');

            const popupClosePromise = new Promise((resolve, reject) => {
                const timeout = setTimeout(() => {
                    reject(new Error('Popup did not close within 2 minutes'));
                }, 120000); // 2 minutes timeout

                // Check if popup is already closed
                if (popupPage.isClosed()) {
                    clearTimeout(timeout);
                    console.log('Popup already closed');
                    resolve();
                    return;
                }

                // Listen for close event
                popupPage.on('close', () => {
                    clearTimeout(timeout);
                    console.log('Popup closed via event listener');
                    resolve();
                });

                // Poll for popup closure as fallback
                const pollInterval = setInterval(() => {
                    if (popupPage.isClosed()) {
                        clearTimeout(timeout);
                        clearInterval(pollInterval);
                        console.log('Popup closed via polling');
                        resolve();
                    }
                }, 2000); // Check every 2 seconds
            });

            await popupClosePromise;
            console.log('Popup closed successfully');

            // Give extra time for the main page to process the redirect
            console.log('Waiting for main page to process redirect...');
            await this.page.waitForTimeout(10000); // Wait 10 seconds for processing

            // After popup closes, wait for the main page to be redirected
            console.log('Now waiting for main page redirect to thank you page...');
            await this.waitForRedirectToThankYouPage(this.page);

        } catch (error) {
            console.error('Error handling PayPal popup:', error.message);

            // Check if popup is still open and try to close it
            try {
                if (popupPage && !popupPage.isClosed()) {
                    console.log('Popup still open, attempting to close...');
                    await popupPage.close();
                }
            } catch (closeError) {
                console.log('Failed to close popup:', closeError.message);
            }

            throw error;
        }
    }

    async loginToPaypal(popupPage) {
        const { email, password } = this.paypalCreds;

        console.log('Starting PayPal login process...');

        try {
            // Check if popup is still available before each operation
            if (popupPage.isClosed()) {
                throw new Error('Popup page was closed unexpectedly');
            }

            // Wait for and fill email field
            const emailField = popupPage.locator('#email');
            await emailField.waitFor({ state: 'visible', timeout: 15000 });
            console.log('Filling PayPal email...');
            await emailField.fill(email);

            // Check for Next button (multi-step login)
            const nextButton = popupPage.locator('#btnNext');
            if (await nextButton.count() > 0) {
                console.log('Clicking Next button...');
                await nextButton.click();
                await popupPage.waitForTimeout(3000); // Wait longer for slow connections
            }

            // Check if popup is still available
            if (popupPage.isClosed()) {
                throw new Error('Popup page was closed during login process');
            }

            // Wait for and fill password field
            const passwordField = popupPage.locator('#password');
            await passwordField.waitFor({ state: 'visible', timeout: 15000 });
            console.log('Filling PayPal password...');
            await passwordField.fill(password);

            // Click login button
            const loginButton = popupPage.locator('#btnLogin');
            await loginButton.waitFor({ state: 'visible', timeout: 15000 });
            console.log('Clicking login button...');
            await loginButton.click();

            // Wait for the continue button or next step
            console.log('Waiting for continue button...');
            const continueButton = popupPage.locator('button:has-text("Continue"), [data-testid="submit-button-initial"]');
            await continueButton.waitFor({ state: 'visible', timeout: 20000 });
            console.log('Continue button found - login successful');

            // Click continue button
            await continueButton.click();
            console.log('Clicked continue button - payment should be processed');

            // Wait longer for the payment to be processed
            console.log('Waiting for payment processing (extended time for slow connections)...');

        } catch (error) {
            console.error('Error during PayPal login:', error.message);
            throw error;
        }
    }

    async waitForRedirectToThankYouPage(mainPage, timeout = 120000) { // 2 minutes timeout
        console.log('Waiting for redirect to thank you page (up to 2 minutes)...');

        try {
            // Check if page is still available
            if (mainPage.isClosed()) {
                throw new Error('Main page was closed unexpectedly');
            }

            // Store the current URL for comparison
            const currentUrl = mainPage.url();
            console.log('Current URL before waiting:', currentUrl);

            // Wait for navigation/redirect with multiple possible patterns
            await Promise.race([
                // Wait for URL to change to thank you page patterns
                mainPage.waitForURL(url => url.includes('thankyou'), { timeout }),
                mainPage.waitForURL(url => url.includes('order-complete'), { timeout }),
                mainPage.waitForURL(url => url.includes('checkout') && url.includes('thankyou'), { timeout }),
                mainPage.waitForURL(url => url.includes('success'), { timeout }),
                // Wait for thank you page elements to appear
                mainPage.waitForSelector('#thankyouPage', { timeout }),
                mainPage.waitForSelector('[data-testid="thank-you"]', { timeout }),
                mainPage.waitForSelector('.thankyou', { timeout }),
                mainPage.waitForSelector('[class*="thank"]', { timeout }),
                // Wait for navigation event with longer timeout
                mainPage.waitForLoadState('networkidle', { timeout: Math.min(timeout, 60000) })
            ]);

            console.log('Successfully detected redirect/thank you page');
            console.log('New URL:', mainPage.url());
            return true;

        } catch (error) {
            console.log('Direct redirect detection failed, performing extended fallback checks...');

            try {
                if (!mainPage.isClosed()) {
                    console.log('Current URL during fallback:', mainPage.url());

                    // Give the page more time to load (extended for slow connections)
                    console.log('Waiting additional 15 seconds for slow connection...');
                    await mainPage.waitForTimeout(15000);

                    // Check if thank you elements are present
                    const thankYouSelectors = [
                        '#thankyouPage',
                        '[data-testid="thank-you"]',
                        '.thankyou',
                        '[class*="thank"]',
                        'h1:has-text("Thank you")',
                        'h2:has-text("Thank you")',
                        'text=Thank you',
                        'text=Order complete',
                        'text=Payment successful',
                        'text=Danke', // German
                        'text=Bestellung abgeschlossen' // German
                    ];

                    for (const selector of thankYouSelectors) {
                        const element = mainPage.locator(selector);
                        if (await element.count() > 0) {
                            console.log(`Thank you page element found with selector: ${selector}`);
                            return true;
                        }
                    }

                    // Check URL patterns as fallback
                    const currentUrl = mainPage.url();
                    const thankYouPatterns = [
                        'thankyou',
                        'order-complete',
                        'success',
                        'checkout/thankyou',
                        'payment/success',
                        'danke', // German
                        'bestellung'
                    ];

                    for (const pattern of thankYouPatterns) {
                        if (currentUrl.includes(pattern)) {
                            console.log(`Thank you page detected via URL pattern: ${pattern}`);
                            return true;
                        }
                    }

                    // Poll for changes over time (for very slow connections)
                    console.log('Performing extended polling for thank you page...');
                    for (let i = 0; i < 12; i++) { // Poll for 60 more seconds (12 * 5 seconds)
                        await mainPage.waitForTimeout(5000);

                        // Check URL again
                        const newUrl = mainPage.url();
                        if (newUrl !== currentUrl) {
                            console.log(`URL changed from ${currentUrl} to ${newUrl}`);
                            for (const pattern of thankYouPatterns) {
                                if (newUrl.includes(pattern)) {
                                    console.log(`Thank you page detected via URL pattern after polling: ${pattern}`);
                                    return true;
                                }
                            }
                        }

                        // Check elements again
                        for (const selector of thankYouSelectors) {
                            const element = mainPage.locator(selector);
                            if (await element.count() > 0) {
                                console.log(`Thank you page element found after polling with selector: ${selector}`);
                                return true;
                            }
                        }

                        console.log(`Polling attempt ${i + 1}/12 - still waiting for thank you page...`);
                    }

                    // Take a screenshot for debugging
                    try {
                        await mainPage.screenshot({ path: './debug-current-page.png', fullPage: true });
                        console.log('Debug screenshot saved as debug-current-page.png');
                    } catch (screenshotError) {
                        console.log('Failed to take debug screenshot:', screenshotError.message);
                    }
                }
            } catch (urlError) {
                console.log('Failed to check current page during fallback:', urlError.message);
            }

            throw new Error('Failed to detect thank you page after PayPal payment');
        }
    }

    async getPaypalFrameSelector(page, selectors) {
        for (const selector of selectors) {
            try {
                const frameCount = await page.locator(selector).count();
                if (frameCount > 0) {
                    console.log(`Found PayPal iframe with selector: ${selector}`);
                    return selector;
                }
            } catch (error) {
                console.log(`Error checking selector ${selector}:`, error.message);
            }
        }
        return null;
    }

    // Add this to your PaypalHelper class
    // Improved handlePaypalPopupAndLogin function
    async handlePaypalPopupAndLogin(popupPage) {
        if (!popupPage) {
            throw new Error('No popup page provided to handle PayPal login');
        }

        try {
            console.log('PayPal popup detected, handling login...');

            // Use a more lenient wait approach - wait for any content to appear instead
            try {
                // First try a shorter timeout with networkidle
                await popupPage.waitForLoadState('networkidle', { timeout: 10000 });
            } catch (loadError) {
                console.log('Network idle timeout, continuing anyway as popup is visible');
                // If networkidle fails, wait for domcontentloaded instead (more reliable)
                await popupPage.waitForLoadState('domcontentloaded', { timeout: 20000 });
            }

            // Take a screenshot for debugging
            await popupPage.screenshot({ path: 'paypal-popup-state.png' });

            // Wait for any recognizable PayPal element to appear
            const recognizableSelectors = [
                '#email', '#password', // Login form
                '#btnNext', '#btnLogin', // Login buttons
                'button:has-text("Pay Now")', // Payment confirmation
                'button:has-text("Continue")', // Continuation button
                '#payment-submit-btn', // Submit button
                '.paypal-button' // Any PayPal button
            ];

            let foundElement = false;
            for (const selector of recognizableSelectors) {
                if (await popupPage.locator(selector).count() > 0) {
                    console.log(`Found PayPal element: ${selector}`);
                    foundElement = true;
                    break;
                }
            }

            if (!foundElement) {
                console.log('No recognizable PayPal elements found, but continuing');
            }

            // Check if we're on the login page by looking for email or password fields
            const hasEmailField = await popupPage.locator('#email').count() > 0;
            const hasPasswordField = await popupPage.locator('#password').count() > 0;

            if (hasEmailField || hasPasswordField) {
                console.log('Login form detected in PayPal popup');

                // Fill email if the field exists
                if (hasEmailField) {
                    const emailField = popupPage.locator('#email');
                    if (await emailField.isVisible()) {
                        await emailField.fill(process.env.PAYPAL_EMAIL);
                        console.log('Filled email field');

                        // Check if there's a "Next" button
                        const nextButton = popupPage.locator('#btnNext');
                        if (await nextButton.isVisible()) {
                            await nextButton.click();
                            console.log('Clicked Next button');
                            await popupPage.waitForTimeout(3000);
                        }
                    }
                }

                // Fill password if the field exists
                if (hasPasswordField || await popupPage.locator('#password').isVisible()) {
                    const passwordField = popupPage.locator('#password');
                    try {
                        await passwordField.waitFor({ state: 'visible', timeout: 10000 });
                        await passwordField.fill(process.env.PAYPAL_PASSWORD);
                        console.log('Filled password field');

                        // Click login
                        const loginButton = popupPage.locator('#btnLogin');
                        if (await loginButton.isVisible()) {
                            await loginButton.click();
                            console.log('Clicked login button');
                        }

                        // Wait for login processing
                        await popupPage.waitForTimeout(5000);
                    } catch (passwordError) {
                        console.log('Error filling password:', passwordError.message);
                    }
                }

                // Wait for any popup changes after login
                await popupPage.waitForTimeout(5000);
            } else {
                console.log('Not on PayPal login page, might be already logged in');
            }

            // Handle various UI elements that might appear in sequence
            const uiElements = [
                { selector: 'button:has-text("Not now")', name: 'Remember device prompt' },
                { selector: 'button:has-text("Accept")', name: 'Accept cookies prompt' },
                { selector: 'button:has-text("Pay Now")', name: 'Pay Now button' },
                { selector: 'button:has-text("Continue")', name: 'Continue button' },
                { selector: 'button:has-text("Agree & Continue")', name: 'Agree & Continue button' },
                { selector: 'button:has-text("Complete Purchase")', name: 'Complete Purchase button' },
                { selector: '#payment-submit-btn', name: 'Payment submit button' }
            ];

            for (const element of uiElements) {
                try {
                    const elementExists = await popupPage.locator(element.selector).count() > 0;
                    if (elementExists) {
                        const isVisible = await popupPage.locator(element.selector).isVisible();
                        if (isVisible) {
                            console.log(`Found and clicking: ${element.name}`);
                            await popupPage.locator(element.selector).click();
                            await popupPage.waitForTimeout(3000);
                        }
                    }
                } catch (elementError) {
                    console.log(`Error handling ${element.name}:`, elementError.message);
                }
            }

            console.log('Waiting for PayPal popup to complete processing...');
            await popupPage.waitForTimeout(10000);

            try {
                const url = await popupPage.url();
                console.log(`Popup is still open. Current URL: ${url}`);
                await popupPage.screenshot({ path: 'paypal-popup-final.png' });
            } catch (urlError) {
                console.log('Popup appears to be closed (error getting URL)');
            }

            console.log('PayPal popup handling completed');
            return true;
        } catch (error) {
            console.error('Error handling PayPal popup:', error.message);

            try {
                await popupPage.screenshot({ path: 'paypal-popup-error.png' });
            } catch (screenshotError) {
                console.log('Failed to take popup screenshot:', screenshotError.message);
            }

            throw error;
        }
    }
}
