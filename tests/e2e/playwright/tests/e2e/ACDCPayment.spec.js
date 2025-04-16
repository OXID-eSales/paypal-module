import { test, expect } from '@playwright/test';
import {ShopHelper} from "../../helpers/ShopHelper";
import {PaypalHelper} from "../../helpers/PaypalHelper";
import dotenv from 'dotenv';

dotenv.config();

test.skip('ACDC Payment test', () => {
    let context, page;

    test.beforeAll(async ({ browser }) => {
        const context = await browser.newContext({
            extraHTTPHeaders: {
                'ngrok-skip-browser-warning': 'true',
            },
        });

        await context.clearCookies();

        page = await context.newPage();
        page.setDefaultTimeout(30000);
        page.setDefaultNavigationTimeout(30000);
        await page.goto(process.env.BASE_URL);
    });


    test('Pay with card and do not save it ', async () => {
        const shopHelper = new ShopHelper(page);
        await shopHelper.loginUser();
        await shopHelper.addItemsToCart();
        await shopHelper.checkout();
        await shopHelper.selectPaymentMethod( 'Card');
        await creditCardHelper.payNotSave();
        await shopHelper.nextStep();
        await shopHelper.orderNow();

        const paypalHelper = new PaypalHelper(page)
        await paypalHelper.loginSandboxPaypal();

        await page.waitForTimeout(10000);
        const thankYouText = await page.locator('#thankyouPage').isVisible();
        expect(thankYouText).toBeTruthy();
    });

    test('Pay with card and save it', async () => {
        const shopHelper = new ShopHelper(page);
        await shopHelper.loginUser();
        await shopHelper.addItemsToCart();
        await shopHelper.checkout();
        await shopHelper.selectPaymentMethodPaypal( 'Card');
        await creditCardHelper.addNewCard('card1');
        await shopHelper.nextStep();
        await shopHelper.orderNow();

        const paypalHelper = new PaypalHelper(page)
        await paypalHelper.loginSandboxPaypal();

        await page.waitForTimeout(10000);
        const thankYouText = await page.locator('#thankyouPage').isVisible();
        expect(thankYouText).toBeTruthy();

        await shopHelper.openSavedPayments();
        const isCardVisible = await page.locator('dummy_creditcard').isVisible();
        expect(isCardVisible).toBeTruthy();
    });

    test('Add second card', async () => {
        const shopHelper = new ShopHelper(page);
        await shopHelper.loginUser();
        await shopHelper.addItemsToCart();
        await shopHelper.checkout();
        await shopHelper.selectPaymentMethodPaypal( 'Card');
        await creditCardHelper.addNewCard('card2');
        await shopHelper.nextStep();
        await shopHelper.orderNow();

        const paypalHelper = new PaypalHelper(page)
        await paypalHelper.loginSandboxPaypal();

        await page.waitForTimeout(10000);
        const thankYouText = await page.locator('#thankyouPage').isVisible();
        expect(thankYouText).toBeTruthy();

        await shopHelper.openSavedPayments();
        const isCardVisible = await page.locator('dummy_creditcard2').isVisible();
        expect(isCardVisible).toBeTruthy();
    });

    test('Pay with saved card', async () => {

    });

    test('Pay with saved card and payment failed', async () => {

    });
});
