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

        // default iframe selectors (used by clickPaypalButtonInIframe with no args)
        this._paypalIframeSelectors = [
            'iframe[title="PayPal"]',
            'iframe[title*="PayPal"]',
            '.component-frame.visible',
            'iframe[src*="paypal"]'
        ];

        // stash the popup between steps
        this._lastPopupPage = null;
    }

    // ===== Public API used by your test =====

    async clickPaypalButtonInIframe(option = 'Paypal') {
        const page = this.page;

        // Map option → iframe index
        const OPTION_TO_INDEX = {
            0: 0,
            1: 1,
            PaypalExpress: 0,
            Paypal: 1
        };
        const preferIndex = Object.prototype.hasOwnProperty.call(OPTION_TO_INDEX, option)
            ? OPTION_TO_INDEX[option]
            : 1;

        // Find a matching iframe selector
        const paypalFrameSelector = await this._findMatchingIframeSelector(this._paypalIframeSelectors);
        if (!paypalFrameSelector) throw new Error('PayPal iframe not found');

        // Listen for popup BEFORE clicking
        const popupPromise = new Promise((resolve, reject) => {
            const timeout = setTimeout(() => reject(new Error('Popup timeout - no popup detected within 20 seconds')), 20000);
            page.once('popup', (popup) => {
                clearTimeout(timeout);
                console.log('Popup detected via event listener!');
                console.log('Popup URL:', popup.url());
                resolve(popup);
            });
        });

        console.log(`Clicking PayPal button in iframe... (option: ${option}, index: ${preferIndex})`);

        // ✅ Pass a single object argument to evaluate
        const clicked = await page.evaluate(({ selector, idx }) => {
            const iframes = document.querySelectorAll(selector);
            console.log(`Found ${iframes.length} iframes for selector: ${selector}`);

            const chosen = iframes[idx] || iframes[0]; // fallback to first if idx out of range
            const usedIndex = chosen ? Array.prototype.indexOf.call(iframes, chosen) : -1;
            console.log(`Requested iframe index: ${idx}, actually using: ${usedIndex}`);
            if (!chosen || !chosen.contentDocument) return false;

            const doc = chosen.contentDocument;
            const buttons = doc.querySelectorAll('[data-funding-source="paypal"], [role="button"], .paypal-button, button');
            console.log('Found buttons in iframe:', buttons.length);
            if (buttons.length === 0) return false;

            const btn = buttons[0];
            console.log('Button to click (outerHTML):', btn.outerHTML);

            if (typeof btn.click === 'function') btn.click();
            else btn.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, view: window }));

            return true;
        }, { selector: paypalFrameSelector, idx: preferIndex });

        if (!clicked) throw new Error('PayPal button click failed inside iframe');
        console.log('PayPal button clicked successfully.');

        try {
            this._lastPopupPage = await popupPromise;
            return this._lastPopupPage;
        } catch (error) {
            console.error('Failed to detect popup:', error.message);
            console.log('Attempting manual popup detection...');
            await page.waitForTimeout(5000);
            const newPages = this.context.pages().filter(p => p !== page);
            if (newPages.length) {
                console.log('Found popup via manual detection');
                this._lastPopupPage = newPages[newPages.length - 1];
                return this._lastPopupPage;
            }
            throw new Error('No popup detected via any method');
        }
    }



    async handlePopup() {
        if (!this._lastPopupPage) {
            throw new Error('HandlePopup called before clickPaypalButtonInIframe (no popup stored)');
        }
        await this.handlePaypalPopup(this._lastPopupPage, async (popup) => {
            await this.loginToPaypal(popup);
        });
        console.log('Returned from PayPal popup; back on main page.');
    }

    async verifyThankYouPage(thankYouSelector = '#thankyouPage', timeoutMs = 60000) {
        console.log('Verifying thank you page...');
        await this.page.waitForSelector(thankYouSelector, { timeout: timeoutMs });
        const visible = await this.page.locator(thankYouSelector).isVisible();
        if (!visible) throw new Error('Thank you page not visible');
        console.log('Payment successfully completed and thank you page displayed!');
    }

    // ===== Your existing implementations (left intact) =====

    async handlePaypalPopup(popupPage, loginCallback) {
        if (!popupPage) throw new Error('No popup page available');
        console.log('Handling PayPal popup...');
        try {
            await popupPage.waitForLoadState('domcontentloaded', { timeout: 20000 });
            console.log('Popup loaded successfully');

            await loginCallback(popupPage);

            console.log('Waiting for popup to close (allowing up to 2 minutes for slow connections)...');
            const popupClosePromise = new Promise((resolve, reject) => {
                const timeout = setTimeout(() => {
                    reject(new Error('Popup did not close within 2 minutes'));
                }, 120000);
                if (popupPage.isClosed()) {
                    clearTimeout(timeout);
                    console.log('Popup already closed');
                    resolve();
                    return;
                }
                popupPage.on('close', () => {
                    clearTimeout(timeout);
                    console.log('Popup closed via event listener');
                    resolve();
                });
                const pollInterval = setInterval(() => {
                    if (popupPage.isClosed()) {
                        clearTimeout(timeout);
                        clearInterval(pollInterval);
                        console.log('Popup closed via polling');
                        resolve();
                    }
                }, 2000);
            });

            await popupClosePromise;
            console.log('Popup closed successfully');

            console.log('Waiting for main page to process redirect...');
            await this.page.waitForTimeout(10000);

            // console.log('Now waiting for main page redirect to thank you page...');
            // await this.waitForRedirectToThankYouPage(this.page);

        } catch (error) {
            console.error('Error handling PayPal popup:', error.message);
            try {
                if (popupPage && !popupPage.isClosed()) {
                    console.log('Popup still open, attempting to close...');
                    await popupPage.close();
                }
            } catch (closeError) {
                console.log('Failed to take close popup:', closeError.message);
            }
            throw error;
        }
    }

    async loginToPaypal(popupPage) {
        const { email, password } = this.paypalCreds;
        console.log('Starting PayPal login process...');
        try {
            if (popupPage.isClosed()) throw new Error('Popup page was closed unexpectedly');
            const emailField = popupPage.locator('#email');
            await emailField.waitFor({ state: 'visible', timeout: 15000 });
            console.log('Filling PayPal email...');
            await emailField.fill(email);
            const nextButton = popupPage.locator('#btnNext');
            if (await nextButton.count() > 0) {
                console.log('Clicking Next button...');
                await nextButton.click();
                await popupPage.waitForTimeout(3000);
            }
            if (popupPage.isClosed()) throw new Error('Popup page was closed during login process');
            const passwordField = popupPage.locator('#password');
            await passwordField.waitFor({ state: 'visible', timeout: 15000 });
            console.log('Filling PayPal password...');
            await passwordField.fill(password);
            const loginButton = popupPage.locator('#btnLogin');
            await loginButton.waitFor({ state: 'visible', timeout: 15000 });
            console.log('Clicking login button...');
            await loginButton.click();
            console.log('Waiting for continue button...');
            const continueButton = popupPage.locator('button:has-text("Continue"), [data-testid="submit-button-initial"]');
            await continueButton.waitFor({ state: 'visible', timeout: 20000 });
            console.log('Continue button found - login successful');
            await continueButton.click();
            console.log('Clicked continue button - payment should be processed');
        } catch (error) {
            console.error('Error during PayPal login:', error.message);
            throw error;
        }
    }

    async waitForRedirectToThankYouPage(mainPage, timeout = 120000) {
        console.log('Waiting for redirect to thank you page (up to 2 minutes)...');
        try {
            if (mainPage.isClosed()) throw new Error('Main page was closed unexpectedly');
            const currentUrl = mainPage.url();
            console.log('Current URL before waiting:', currentUrl);
            await Promise.race([
                mainPage.waitForURL(url => url.includes('thankyou'), { timeout }),
                mainPage.waitForURL(url => url.includes('order-complete'), { timeout }),
                mainPage.waitForURL(url => url.includes('checkout') && url.includes('thankyou'), { timeout }),
                mainPage.waitForURL(url => url.includes('success'), { timeout }),
                mainPage.waitForSelector('#thankyouPage', { timeout }),
                mainPage.waitForSelector('[data-testid="thank-you"]', { timeout }),
                mainPage.waitForSelector('.thankyou', { timeout }),
                mainPage.waitForSelector('[class*="thank"]', { timeout }),
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
                    console.log('Waiting additional 15 seconds for slow connection...');
                    await mainPage.waitForTimeout(15000);

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
                        'text=Danke',
                        'text=Bestellung abgeschlossen'
                    ];
                    for (const selector of thankYouSelectors) {
                        const el = mainPage.locator(selector);
                        if (await el.count() > 0) {
                            console.log(`Thank you page element found with selector: ${selector}`);
                            return true;
                        }
                    }
                    const url = mainPage.url();
                    const patterns = [
                        'thankyou', 'order-complete', 'success', 'checkout/thankyou',
                        'payment/success', 'danke', 'bestellung'
                    ];
                    for (const p of patterns) {
                        if (url.includes(p)) {
                            console.log(`Thank you page detected via URL pattern: ${p}`);
                            return true;
                        }
                    }
                    console.log('Performing extended polling for thank you page...');
                    for (let i = 0; i < 12; i++) {
                        await mainPage.waitForTimeout(5000);

                        const newUrl = mainPage.url();
                        if (newUrl !== url) {
                            console.log(`URL changed from ${url} to ${newUrl}`);
                            for (const p of patterns) {
                                if (newUrl.includes(p)) {
                                    console.log(`Thank you page detected via URL pattern after polling: ${p}`);
                                    return true;
                                }
                            }
                        }
                        for (const selector of thankYouSelectors) {
                            const el = mainPage.locator(selector);
                            if (await el.count() > 0) {
                                console.log(`Thank you page element found after polling with selector: ${selector}`);
                                return true;
                            }
                        }
                        console.log(`Polling attempt ${i + 1}/12 - still waiting for thank you page...`);
                    }
                    try {
                        await mainPage.screenshot({ path: './debug-current-page.png', fullPage: true });
                        console.log('Debug screenshot saved as debug-current-page.png');
                    } catch (shotErr) {
                        console.log('Failed to take debug screenshot:', shotErr.message);
                    }
                }
            } catch (urlErr) {
                console.log('Failed to check current page during fallback:', urlErr.message);
            }
            throw new Error('Failed to detect thank you page after PayPal payment');
        }
    }

    // ===== private util =====
    async _findMatchingIframeSelector(selectors) {
        for (const selector of selectors) {
            try {
                const frameCount = await this.page.locator(selector).count();
                if (frameCount > 0) {
                    console.log(`Found PayPal iframe with selector: ${selector}`);
                    return selector;
                }
            } catch (err) {
                console.log(`Error checking selector ${selector}:`, err.message);
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
