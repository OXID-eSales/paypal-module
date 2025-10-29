import { test, expect } from '@playwright/test';
import {ShopHelper} from "../../helpers/ShopHelper";
import {PaypalHelper} from "../../helpers/PaypalHelper";
import dotenv from 'dotenv';

dotenv.config();

// test('GooglePay test', () => {
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


    test.skip('Should complete GooglePay payment end-to-end', async () => {
        const shopHelper = new ShopHelper(page);
        await shopHelper.loginUser();
        await shopHelper.addItemsToCart();
        await shopHelper.checkout();
        await shopHelper.selectPaymentMethod( 'googlepay');
        await shopHelper.nextStep();
        await shopHelper.acceptTerms()
        await shopHelper.clickGooglePay(page);
        await shopHelper.useGooglePaySignIn()
        const thankYouText = await page.locator('#thankyouPage').isVisible();
        expect(thankYouText).toBeTruthy();
    });
// });
