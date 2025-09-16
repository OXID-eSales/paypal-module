import {UserNotFound} from './Exceptions/UserNotFound';


export class ShopHelper {

    constructor(page) {
        if (!page) {
            throw new Error('Invalid or undefined "page" object provided to ShopHelper.');
        }

        this.page = page; // Store the Playwright 'page' instance
    }

    async clickUserCenter() {
        await this.page.locator('.menu-dropdowns button[aria-label="Usercenter"]').click();
    }

    async loginUser() {
        const { page } = this;

        try {
            await this.login();
            return;
        } catch (error) {
            const ifMyAccount = await this.ifUserLoggedIn();
            if (!ifMyAccount) {
                console.warn('User not registered, proceeding to registration...');
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
        }

        throw new UserNotFound();
    }

    async ifUserLoggedIn() {
        const { page } = this;
        return await page.locator('div.menu-dropdowns > ul').count() > 0;
    }


    async addItemsToCart() {
        const { page } = this;

        const articleSlider = page.locator('.article-slider');
        const products = await articleSlider.locator('.card.product-card');
        const maxProducts = 3; // Limit the number of products to add

        for (let i = 0; i < maxProducts; i++) {
            const product = products.nth(i);
            const addToCartButton = product.locator('[type="submit"]');

            if (await addToCartButton.count() > 0) {
                await addToCartButton.click();

                await page.waitForTimeout(2000); // Ensure modal or confirmation is processed
            }
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

        await page.locator('.btn.btn-minibasket').nth(0).click();
        await page.locator('.modal-body').waitFor({ state: 'visible' });
        const firstLink = page.locator('.modal-body a').first();
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
    async selectPaymentMethodCard() {
        const { page } = this;
        // Select the ACDC radio button
        await page.locator(`input[type="radio"][value="oscpaypal_acdc"]`).click();
    }

    async nextStep() {
        const { page } = this;

        await page.locator('button').locator('text=/Next|Weiter/i').click();
    }


    async orderNow() {
        const { page } = this;

        await page.waitForTimeout(5000);
        const orderButtonLocator = page.locator('text=/Order now|Zahlungspflichtig bestellen/i');

        console.log('Checking for Order button:', await orderButtonLocator.count());
        if (await orderButtonLocator.isVisible()) {
            console.log('Order button is visible.');
            await orderButtonLocator.scrollIntoViewIfNeeded();
            await orderButtonLocator.click();
            return;
        }

        const paypalIframeLocator = await page.frameLocator('.component-frame.visible').nth(0);
        console.log('Targeting refined PayPal iframe selector.');

        // const paypalButtonLocator = paypalIframeLocator.locator('div.paypal-button');
        const paypalButtonLocator = await paypalIframeLocator.locator('[data-funding-source="paypal"]');

        console.log('PayPal button is visible. Clicking...');
        await paypalButtonLocator.click();

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

    async clickSavePayment(page) {
        await page.click('label[for="oscPayPalVaultPaymentCheckbox"]')   }

}
