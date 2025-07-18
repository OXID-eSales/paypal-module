import { test, expect } from '@playwright/test';
import {ShopHelper} from "../../helpers/ShopHelper";
import {PaypalHelper} from "../../helpers/PaypalHelper";
import dotenv from 'dotenv';

dotenv.config();

test.skip('GooglePay test', () => {
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


    test('Should complete GooglePay payment end-to-end', async () => {
        const shopHelper = new ShopHelper(page);
        await shopHelper.loginUser();
        await shopHelper.addItemsToCart();
        await shopHelper.checkout();
        await shopHelper.selectPaymentMethod( 'GooglePay');
        await shopHelper.nextStep();
        await shopHelper.orderNow();

        const paypalHelper = new PaypalHelper(page)
        await paypalHelper.loginSandboxPaypal();

        await shopHelper.clickGooglePay(page);

        //TODO: finish payment

        await page.waitForTimeout(10000);
        const thankYouText = await page.locator('#thankyouPage').isVisible();
        expect(thankYouText).toBeTruthy();
    });
});
