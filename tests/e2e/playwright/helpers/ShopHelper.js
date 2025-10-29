import {UserNotFound} from './Exceptions/UserNotFound';

export class ShopHelper {

    constructor(page) {
        if (!page) {
            throw new Error('Invalid or undefined "page" object provided to ShopHelper.');
        }
        this.page = page;
    }

    async clickUserCenter() {
        console.log('Clicking on the user center (login button)');
        await this.page.locator('.service-menu.showLogin').click();
    }

    async loginUser() {
        const { page } = this;

        console.log('Attempting to log in...');
        try {
            await this.login();
            console.log('Login attempt finished.');
            return;
        } catch (error) {
            console.error('Login failed:', error);
            // If needed, handle further logic here (e.g., registration)
        }
    }

    async login() {
        const { page } = this;

        console.log('Navigating to the user center for login...');
        await this.clickUserCenter();

        const loggedIn = await this.ifUserLoggedIn();
        console.log('User logged in check:', loggedIn);

        if (loggedIn) {
            console.log('User already logged in.');
            return;
        }

        console.log('Filling in login credentials...');
        await page.locator('form[name="login"] #loginEmail').fill(process.env.SHOP_USER_EMAIL);
        await page.locator('form[name="login"] #loginPasword').fill(process.env.SHOP_USER_PASSWORD);
        await page.locator('form[name="login"] button[type="submit"]').click();

        console.log('Submitting login form...');
        await page.waitForTimeout(5000);

        try {
            const isLoggedIn = await this.ifUserLoggedIn();
            console.log('User login status after submitting:', isLoggedIn);
            if (isLoggedIn) {
                console.log('Login successful.');
                return;
            }
        } catch (error) {
            console.error('Error checking if user is logged in:', error);
            console.warn('Login failed. User is not registered.');
        }

        throw new Error('UserNotFound');
    }

    async ifUserLoggedIn() {
        const { page } = this;
        const userLoggedIn = await page.locator('div.menu-dropdowns > ul').count();
        console.log('User login status (count of user-related menu items):', userLoggedIn > 0 ? 'Logged in' : 'Not logged in');
        return userLoggedIn > 0;
    }

    // async addItemsToCart() {
    //     const { page } = this;
    //     const products = await page.locator('.productData');
    //     const maxProducts = 3;
    //
    //     for (let i = 0; i < maxProducts; i++) {
    //         const product = products.nth(i);
    //         const addToCartButton = product.locator('.btn-default[type="submit"]');
    //
    //         if (await addToCartButton.count() > 0) {
    //             await addToCartButton.click();
    //             console.log(`Added product ${i + 1} to the cart.`);
    //             await page.waitForTimeout(2000);
    //
    //             const closeModalButton = page.locator('.modal-dialog .modal-header .close');
    //             await closeModalButton.click();
    //             console.log('Modal closed.');
    //         }
    //     }

    //     await page.waitForTimeout(5000);
    //     console.log('Finished adding products to cart.');
    // }

    async _getCartCount() {
        const { page } = this;

        // Try the badge on the mini-basket button
        const badge = page.locator('.minibasket-menu .badge').first();
        if (await badge.count()) {
            const raw = await badge.innerText().catch(() => '0');
            const n = parseInt((raw || '').replace(/[^\d]/g, ''), 10);
            if (!Number.isNaN(n)) return n;
        }

        // Fallback: open dropdown and parse "X Items in cart"
        const toggle = page.locator('.minibasket-menu > button.dropdown-toggle').first();
        if (await toggle.count()) {
            await toggle.click().catch(() => {});
            const title = page.locator('.minibasket-menu .title strong').first();
            if (await title.count()) {
                const txt = (await title.innerText().catch(() => '')).trim(); // e.g., "1 Items in cart"
                const m = txt.match(/(\d+)\s+Items?\s+in\s+cart/i);
                if (m) return parseInt(m[1], 10);
            }
            // Close dropdown by clicking toggle again (best effort)
            await toggle.click().catch(() => {});
        }

        // Default to 0 if we can't determine
        return 0;
    }

    async addItemsToCart() {
        const { page } = this;

        // If there are already items, don’t add more
        const existingCount = await this._getCartCount();
        if (existingCount > 0) {
            console.log(`Cart already has ${existingCount} item(s). Skipping add-to-cart.`);
            return;
        }

        const products = page.locator('.productData');
        const total = await products.count();
        const maxProducts = Math.min(3, total);

        for (let i = 0; i < maxProducts; i++) {
            const product = products.nth(i);
            const addToCartButton = product.locator('.btn-default[type="submit"]');

            if (await addToCartButton.count()) {
                await addToCartButton.click();
                console.log(`Added product ${i + 1} to the cart.`);

                // small settle for modal to appear
                await page.waitForTimeout(500);

                // Close modal if it shows up (best effort)
                const closeModalButton = page.locator('.modal-dialog .modal-header .close');
                if (await closeModalButton.isVisible().catch(() => false)) {
                    await closeModalButton.click().catch(() => {});
                    console.log('Modal closed.');
                }

                // small settle between adds
                await page.waitForTimeout(1000);
            }
        }

        // Optional: verify cart count increased
        const newCount = await this._getCartCount();
        console.log(`Finished adding products. Cart count: ${newCount}`);
    }


    async changeCountry(country) {
        const { page } = this;

        // --- Navigate to Billing → Edit Address ---
        console.log('[changeCountry] Opening user services → Billing → Edit address…');
        await page.locator('.service-menu').click();
        await page.locator('#services').locator('text=/My account|Mein Konto/i').click();
        await page.locator('.list-group-item').locator('text=/Billing|Rechnung/i').click();
        await page.locator('#userChangeAddress').click();

        // Helper: read current selected country label from the hidden <select>
        const readSelectedCountry = async () => {
            return await page.evaluate(() => {
                const sel = document.querySelector('#invCountrySelect');
                if (!sel) return '';
                const opt = sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
                return (opt?.label || opt?.text || '').trim();
            });
        };

        // Log current selected country
        const beforeLabel = await readSelectedCountry();
        console.log(`[changeCountry] Currently selected country: "${beforeLabel || '(none)'}"`);

        // --- Preferred: use the Bootstrap-select dropdown tied to #invCountrySelect ---
        const bsButton = page
            .locator('.btn-group.bootstrap-select.form-control button.dropdown-toggle[data-id="invCountrySelect"]')
            .first();

        if (await bsButton.count()) {
            console.log('[changeCountry] Using Bootstrap-select UI…');
            await bsButton.scrollIntoViewIfNeeded();
            await bsButton.click(); // open dropdown

            // Click the option by visible text
            const option = page.locator('.dropdown-menu.open .inner li a .text', { hasText: country }).first();
            await option.click();
            console.log(`[changeCountry] Clicked option: "${country}"`);

            // Verify via the hidden select (source of truth)
            const afterLabel = await readSelectedCountry();
            console.log(`[changeCountry] Selected country after change: "${afterLabel || '(none)'}"`);

            if (!afterLabel || afterLabel.toLowerCase() !== country.toLowerCase()) {
                console.warn(`[changeCountry] Mismatch after selection. Expected "${country}", got "${afterLabel}".`);
                // Try dispatching change just in case the UI didn’t sync
                await page.evaluate(() => {
                    const sel = document.querySelector('#invCountrySelect');
                    if (sel) sel.dispatchEvent(new Event('change', { bubbles: true }));
                });
            }

            // Close any leftover open menu to avoid overlay issues
            const menuOpen = page.locator('.dropdown-menu.open');
            if (await menuOpen.count()) {
                await bsButton.click().catch(() => {});
                await page.keyboard.press('Escape').catch(() => {});
            }
        } else {
            console.log('[changeCountry] Bootstrap-select UI not found; forcing hidden <select>…');
            const select = page.locator('#invCountrySelect');
            await select.waitFor({ state: 'attached' });
            await select.selectOption({ label: country }, { force: true });

            // Fire change event to sync wrappers
            await page.evaluate(() => {
                const sel = document.querySelector('#invCountrySelect');
                if (sel) sel.dispatchEvent(new Event('change', { bubbles: true }));
            });

            const afterLabel = await readSelectedCountry();
            console.log(`[changeCountry] Selected country after forced select: "${afterLabel || '(none)'}"`);
            if (!afterLabel || afterLabel.toLowerCase() !== country.toLowerCase()) {
                throw new Error(`[changeCountry] Failed to set country to "${country}". Current: "${afterLabel}".`);
            }
        }

        // --- Save changes ---
        console.log('[changeCountry] Saving address changes…');
        await page.locator('#accUserSaveTop').click();

        // --- Go to homepage by clicking the logo (instead of "Home") ---
        console.log('[changeCountry] Navigating to homepage via logo…');
        const logo = page.locator('img[alt="OXID Surf and Kite Shop"], img[src*="logo_oxid"]');
        if (await logo.count()) {
            await logo.first().scrollIntoViewIfNeeded();
            await logo.first().click();
        } else {
            console.warn('[changeCountry] Logo not found, falling back to Home link.');
            await page.locator('#navigation').locator('text=Home').click();
        }

        // Wait for main content
        await page.locator('#content').waitFor({ state: 'visible' });
        console.log('[changeCountry] Done.');
    }



    async checkout() {
        const { page } = this;
        await page.locator('.btn-group.minibasket-menu button').click();
        console.log('Cart dropdown button clicked.')
        await page.locator('.minibasket-menu-box').waitFor({ state: 'visible' });
        console.log('Basket flyout visible.');
        const checkoutButton = page.locator('.minibasket-menu-box .btn.btn-primary');
        await checkoutButton.waitFor({ state: 'visible' });
        await checkoutButton.scrollIntoViewIfNeeded();
        console.log('Checkout button ready to click.');
        await checkoutButton.click();
        console.log('Checkout button clicked.');
        await page.waitForLoadState('domcontentloaded');
        console.log('Page loaded after checkout.');
    }

    async selectPaymentMethod(paymentMethod) {
        const { page } = this;
        const paymentMethodMap = {
            'Paypal': 'oscpaypal',
            'Paypal Credit or Debit Card': 'oscpaypal_acdc',
            'Paypal GooglePay': 'oscpaypal_googlepay',
            'Paypal Pay upon Invoice': 'oscpaypal_pui',
            'Paypal SEPA direct debit': 'oscpaypal_sepa',
            'Paypal Bancontact': 'oscpaypal_bancontact',
            'Paypal EPS': 'oscpaypal_eps',
            'Paypal iDeal': 'oscpaypal_iDeal',
            'Paypal P24': 'oscpaypal_przelewy24'
        };

        const normalizedValue = paymentMethodMap[paymentMethod];
        await page.waitForTimeout(5000);
        if (normalizedValue) {
            // Wait for the radio button to be visible
            await page.waitForSelector(`input[type="radio"][value="${normalizedValue}"]:visible`);

            // Ensure the radio button is scrolled into view and clicked
            await page.locator(`input[type="radio"][value="${normalizedValue}"]`).scrollIntoViewIfNeeded();
            await page.locator(`input[type="radio"][value="${normalizedValue}"]`).click();

            console.log(`Payment method '${paymentMethod}' selected.`);
        } else {
            console.log(`Payment method '${paymentMethod}' is not a PayPal option.`);
        }
    }


    async nextStep() {
        const { page } = this;
        await page.locator('button').locator('text=/Next|Weiter/i').click();
    }

    async acceptTerms() {
        const { page } = this;
        const termsCheckbox = page.locator('#checkAgbTop');
        await termsCheckbox.waitFor({ state: 'visible' });
        if (await termsCheckbox.isChecked() === false) {
            await termsCheckbox.click();
            console.log('Terms and Conditions checkbox clicked.');
        } else {
            console.log('Terms and Conditions checkbox is already checked.');
        }
    }

    async orderNow(index = 1, { timeoutMs = 20000 } = {}) {
        const { page } = this;
        // optional pause (your original had a fixed 20s wait)
        await page.waitForTimeout(timeoutMs);
        const selector = 'text=/Order now|Zahlungspflichtig bestellen/i';
        const buttons = page.locator(selector);
        const total = await buttons.count();
        console.log(`[orderNow] Found ${total} candidate button(s) for selector: ${selector}`);

        if (total === 0) {
            throw new Error('[orderNow] No "Order now" button found.');
        }
        // normalize index (accepts "1" or 1). Zero-based like Playwright's nth()
        let idx = Number.parseInt(index, 10);
        if (Number.isNaN(idx)) idx = 0;
        if (idx < 0 || idx >= total) {
            console.warn(`[orderNow] Requested index ${index} out of range (0..${total - 1}). Using 0.`);
            idx = 0;
        }
        let target = buttons.nth(idx);
        console.log(`[orderNow] Trying to click button at index ${idx}…`);
        // If the target isn't visible, try first visible fallback
        if (!(await target.isVisible().catch(() => false))) {
            console.warn(`[orderNow] Button at index ${idx} not visible. Searching for first visible…`);
            for (let i = 0; i < total; i++) {
                const candidate = buttons.nth(i);
                if (await candidate.isVisible().catch(() => false)) {
                    console.log(`[orderNow] Using visible button at index ${i} instead.`);
                    target = candidate;
                    break;
                }
            }
        }

        // Final visibility check (best-effort)
        const vis = await target.isVisible().catch(() => false);
        if (!vis) {
            console.warn('[orderNow] Target button still not visible; attempting scroll and click anyway.');
        }

        await target.scrollIntoViewIfNeeded().catch(() => {});
        await target.click().catch(async (err) => {
            console.warn(`[orderNow] Normal click failed: ${err?.message}. Retrying with JavaScript click…`);
            const handle = await target.elementHandle();
            if (!handle) throw new Error('[orderNow] Could not obtain element handle for JS click.');
            await page.evaluate((el) => el.click(), handle);
        });

        console.log('[orderNow] Order button clicked.');
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

    async verifyThankYouPage(thankYouSelector = '#thankyouPage', timeoutMs = 60000) {
        console.log('Verifying thank you page...');
        await this.page.waitForSelector(thankYouSelector, { timeout: timeoutMs });
        const visible = await this.page.locator(thankYouSelector).isVisible();
        if (!visible) throw new Error('Thank you page not visible');
        console.log('Payment successfully completed and thank you page displayed!');

        // const { page } = this;
        // const selector = '#thankyouPage';
        // await page.waitForSelector(selector, { timeout: 60000 });
        // const text = await page.locator(selector).textContent();
        //
        // const mustContain = [
        //     'Thank you for ordering at OXID eShop 6.\n',
        //     'We registered your order with number'
        // ];
        //
        // for (const fragment of mustContain) {
        //     if (!text || !text.includes(fragment)) {
        //         throw new Error(`Thank you page text missing expected fragment: "${fragment}"`);
        //     }
        // }
        // console.log('Thank you page copy validated.');
    }

    async clickGooglePay(page) {
        await page.locator('[aria-label="Buy with GPay"]').click();
    }

    async useGooglePaySignIn() {
        const { page } = this;
        await page.waitForSelector('iframe[src*="signin"]');
        const googlePayIframe = await page.frameLocator('iframe[src*="signin"]');
        console.log('Google Pay sign-in iframe detected.');
        const emailField = googlePayIframe.locator('#identifierId');
        await emailField.waitFor({ state: 'visible' });
        console.log('Filling in email field with GooglePay_User...');
        await emailField.fill(process.env.GOOGLEPAY_USER);
        const nextButton = googlePayIframe.locator('#identifierNext');
        await nextButton.waitFor({ state: 'visible' });
        console.log('Clicking the "Next" button...');
        await nextButton.click();
        await page.waitForTimeout(2000);
        await page.waitForSelector('#thankYouPage', { timeout: 60000 }).catch(() => {});
        console.log('Payment successful (Google Pay flow).');
    }

    async verifyAuthorizationFailed(timeout = 10000) {
        const selector = 'div.alert.alert-danger';
        const text = /The payment authorization failed\.?\s+Please verify your input!?/i;
        const findIn = async (root) => {
            const loc = root.locator(selector).filter({ hasText: text }).first();
            return (await loc.count()) ? loc : null;
        };

        let target = await findIn(this.page);
        if (!target) {
            for (const f of this.page.frames()) {
                target = await findIn(f);
                if (target) break;
            }
        }

        if (!target) {
            throw new Error('Danger alert not found (page + iframes)');
        }

        await expect(target).toBeVisible({ timeout });
        await expect(target).toContainText(text, { timeout });
    }

    async verifyTCAuth() {
        const errorMessage = this.page.locator('.error-message.alert.alert-danger');

        // Check if the error message contains the expected text
        const isErrorVisible = await errorMessage.isVisible();
        if (isErrorVisible) {
            const errorText = await errorMessage.textContent();
            if (errorText.includes('Please read and confirm our terms and conditions!')) {
                console.log('Error found: User needs to confirm the terms and conditions.');
            } else {
                console.log('Different error displayed.');
            }
        } else {
            console.log('No error message displayed.');
        }
        // Return whether the error is present or not
        return isErrorVisible;
    }
}
