import {UserNotFound} from './Exceptions/UserNotFound';


export class ShopHelper {

    constructor(page) {
        if (!page) {
            throw new Error('Invalid or undefined "page" object provided to ShopHelper.');
        }

        this.page = page; // Store the Playwright 'page' instance
    }

    async clickUserCenter() {
        await this.page.locator('.showLogin button[data-toggle="dropdown"]').click();
    }

    async loginUser() {
        const { page } = this;

        try {
            await this.login();
        } catch (error) {
            console.error('Login failed:', error.message);
            const ifMyAccount = await this.ifUserLoggedIn();
            if (!ifMyAccount) {
                console.warn('User not registered');
                throw new UserNotFound();
            }
        }
    }


    async login() {
        const { page } = this;

        await this.clickUserCenter();
        let loggedIn = await this.ifUserLoggedIn();

        if (loggedIn) {
            return;
        }

        await page.locator('form[name="login"] #loginEmail').fill(process.env.SHOP_USER_EMAIL);

        await page.waitForTimeout(5000);

        await page.locator('form[name="login"] #loginPasword').fill(process.env.SHOP_USER_PASSWORD);
        await page.locator('form[name="login"] button[type="submit"]').click();
        await page.waitForTimeout(5000);

        try {
            if (await this.ifUserLoggedIn()) {
                console.log('Login successful.');
                return;
            }
        } catch (error) {
            console.warn('Login failed. User is not registered.');
            throw new UserNotFound();
        }
    }

    async ifUserLoggedIn() {
        const { page } = this;
        return await page.locator('div.menu-dropdowns > ul').count() > 0;
    }


    async addItemsToCart() {
        const { page } = this;

        const articleSlider = page.locator('#newItems');
        const products = await articleSlider.locator('.productData');
        const maxProducts = 3; // Limit the number of products to add


        const product = products.nth(1);
        const addToCartButton = product.locator('button[type="submit"]');

        if (await addToCartButton.count() > 0) {
            await addToCartButton.click();

            await page.waitForTimeout(2000);
            await page.locator('.modal-content [type="button"]')
                .getByText('continue shopping').click();
        }


        await page.waitForTimeout(5000);
    }


    async changeCountry(country) {
        const { page } = this;

        await page.locator('.service-menu').click();
        await page.locator('#services').locator('text=/My account|Mein Konto/i').click();
        await page.locator('.list-group-item').locator('text=/Billing|Rechnung/i').click();
        await page.locator('#userChangeAddress').click();
        await page.locator('#invCountrySelect').selectOption({ label: country });
        await page.locator('#accUserSaveTop').click();
        await page.locator('#navigation').locator('text=Home').click();
        await page.locator('#content').waitFor({ state: 'visible' });
    }


    async checkout() {
        const { page } = this;

        await page.locator('.minibasket-menu button').nth(0).click();
        const firstLink = page.locator('.minibasket-menu-box a').getByText("Checkout");
        await firstLink.click();
        await page.waitForLoadState('domcontentloaded');
    }


    async selectPaymentMethod(paymentMethod) {
        const { page } = this;

        // Normalize the name for selection
        const normalizedContext = paymentMethod.toLowerCase().replace(/\s+/g, '').replace('-', '').trim();

        // Select the PayPal radio button
        await page.locator(`input[type="radio"][value="oscpaypal_${normalizedContext}"]`).click();
    }

    async selectPaymentMethodPaypal() {
        const { page } = this;
        // Select the PayPal radio button
        await page.locator(`input[type="radio"][value="oscpaypal"]`).click();
    }


    async nextStep() {
        const { page } = this;

        await page.locator('button').locator('text=/Next|Weiter/i').click();
    }


    async orderNow() {
        const { page } = this;

        await page.waitForTimeout(5000);

         const paypalButton = await this.findPayPalButton(page);
         await paypalButton.click();

        console.log('PayPal button clicked successfully.');
    }

    async loginSandboxPaypal() {
        const { page } = this;

        await page.waitForTimeout(5000);

        await page.locator('#email').clear();
        await page.locator('#email').fill(process.env.PAYPAL_EMAIL);

        await page.locator('#password').clear();
        await page.locator('#password').fill(process.env.PAYPAL_PASSWORD);

        await page.locator('.actions #btnLogin').click();
        await page.locator('[data-testid="submit-button-initial"]').click();

        await page.waitForTimeout(2000);
    }


    async thankYouPage() {
        const { page } = this;

        const text = await page.locator('#thankyouPage').textContent();
        expect(text).toContain('Thank you for ordering at Oxid eShop 6.');
        expect(text).toContain('We registered your order with number');
    }

    async clickGooglePay(page) {
        await page.locator('[aria-label="Buy with GPay"]').click();
    }

    /**
     * Finds and returns the PayPal button inside the iframe on the order confirmation page
     * @param {Page} page - Playwright page object
     * @returns {Promise<Locator>} - Locator for the PayPal button
     *
     * // Usage example:
     * // const paypalButton = await findPayPalButton(page);
     * // await paypalButton.click();
     */
    async findPayPalButton(page) {
        // First, locate the PayPal iframe container
        const paypalContainer = page.locator('#oscpaypal .paypal-buttons');
        await paypalContainer.waitFor({ state: 'attached', timeout: 10000 });

        // Get the iframe within the PayPal container
        const iframe = paypalContainer.locator('iframe.component-frame');
        await iframe.waitFor({ state: 'visible', timeout: 10000 });

        // Get the iframe's content frame
        const frameHandle = await iframe.contentFrame();
        if (!frameHandle) {
            throw new Error('Could not access PayPal button iframe content');
        }

        // Within the iframe, locate the PayPal button by its distinctive container and logo
        return frameHandle.locator('[data-funding-source="paypal"]')
    }

    /**
     * Clicks the PayPal button using JavaScript evaluation to bypass overlay issues
     * @returns {Promise<void>}
     */
    async clickPayPalButton() {
        const { page } = this;

        // Wait for the PayPal button container to be ready
        await page.waitForSelector('#oscpaypal .paypal-buttons', { timeout: 10000 });

        // Try to find the iframe containing the PayPal button
        const iframeSelector = 'iframe.component-frame';

        try {
            // Use JavaScript to find and click the PayPal button inside the iframe
            await page.evaluate(async (selector) => {
                // Find the iframe
                const iframe = document.querySelector(selector);
                if (!iframe || !iframe.contentDocument) {
                    throw new Error('PayPal iframe not found or not accessible');
                }

                // Find the PayPal button inside the iframe
                const paypalButton = iframe.contentDocument.querySelector('[data-funding-source="paypal"]');
                if (!paypalButton) {
                    throw new Error('PayPal button not found inside iframe');
                }

                // Click the button
                paypalButton.click();
                return true;
            }, `#oscpaypal .paypal-buttons ${iframeSelector}`);

            console.log('PayPal button clicked via JavaScript');

            // Wait a moment for the click to take effect
            await page.waitForTimeout(2000);

        } catch (error) {
            console.error('JavaScript click failed:', error.message);

            // Alternative method: Try using page.click with force option
            try {
                console.log('Trying alternative click method...');

                // Find the iframe and get access to its content
                const iframe = page.frameLocator(iframeSelector);

                // Try to click the button with force option
                await iframe.locator('[data-funding-source="paypal"]').click({ force: true, timeout: 10000 });
                console.log('PayPal button clicked with force option');

            } catch (secondError) {
                console.error('Alternative click method failed:', secondError.message);

                // Last resort: Try using mouse actions directly
                try {
                    // Find the position of the iframe
                    const iframeElement = await page.locator(`#oscpaypal .paypal-buttons ${iframeSelector}`);
                    const box = await iframeElement.boundingBox();

                    if (box) {
                        // Click in the middle of the iframe
                        await page.mouse.click(box.x + box.width / 2, box.y + box.height / 2);
                        console.log('Clicked in the middle of the PayPal iframe');
                    } else {
                        throw new Error('Could not determine iframe position');
                    }
                } catch (finalError) {
                    throw new Error(`All PayPal button click methods failed: ${finalError.message}`);
                }
            }
        }

        // Wait for any popup or redirect that might happen after clicking
        await page.waitForTimeout(5000);
    }

    async clickPaypalButtonAndGetPopup() {
        const { page } = this;

        console.log('Looking for PayPal button in iframe...');

        // Wait for the PayPal button container to be ready
        await page.waitForSelector('#oscpaypal .paypal-buttons', { timeout: 15000 });

        // Find all iframes and log them for debugging
        const iframeHandles = await page.$$('iframe');
        console.log(`Found ${iframeHandles.length} iframes on page`);

        // Get the popup page when the PayPal button is clicked
        let popupPage = null;

        // Set up the popup listener before clicking
        const popupPromise = new Promise((resolve) => {
            page.context().on('page', async (newPage) => {
                console.log('New page detected!');
                resolve(newPage);
            });
        });

        // Try to click the PayPal button through the iframe
        try {
            // Find and click the PayPal button in the iframe
            const frameElementHandle = await page.$('#oscpaypal .paypal-buttons iframe.component-frame');

            if (frameElementHandle) {
                const frame = await frameElementHandle.contentFrame();

                if (frame) {
                    const paypalButtons = await frame.$$('[data-funding-source="paypal"]');
                    console.log(`Found buttons in iframe: ${paypalButtons.length}`);

                    if (paypalButtons.length > 0) {
                        // Click the button using JavaScript inside the frame
                        await frame.evaluate((selector) => {
                            const button = document.querySelector(selector);
                            if (button) button.click();
                        }, '[data-funding-source="paypal"]');

                        console.log('PayPal button clicked successfully.');
                    } else {
                        throw new Error('PayPal button not found in iframe');
                    }
                } else {
                    throw new Error('Could not access iframe content frame');
                }
            } else {
                throw new Error('PayPal iframe not found');
            }

            // Wait for the popup with timeout
            try {
                console.log('Waiting for popup to appear...');

                // Create a race between popup detection and timeout
                popupPage = await Promise.race([
                    popupPromise,
                    new Promise((_, reject) =>
                        setTimeout(() => reject(new Error('Popup timeout - no popup detected within 20 seconds')), 20000)
                    )
                ]);

                console.log('Popup detected!');
            } catch (popupError) {
                console.error('Failed to detect popup:', popupError.message);

                // Manual popup detection as fallback
                console.log('Attempting manual popup detection...');
                const pages = await page.context().pages();

                // Find a page that's not our main page
                const popupPages = pages.filter(p => p !== page);

                if (popupPages.length > 0) {
                    console.log('Found popup via manual detection');
                    popupPage = popupPages[0];
                } else {
                    throw new Error('No popup detected via manual detection either');
                }
            }

            return popupPage;
        } catch (error) {
            console.error('Error clicking PayPal button or detecting popup:', error.message);
            throw error;
        }
    }
}
