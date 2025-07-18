import * as paypalUtility from '../../PageObjects/paypal/paypalUtility';
import * as login from '../../PageObjects/login/login';
import { test, expect } from '@playwright/test';

test.describe('Non German Successfull Payment Methods Tests', () => {

    let page;
    let context;

    test.beforeAll(async ({ browser }) => {
        context = await browser.newContext();
        page = await context.newPage();
    });

    test.beforeEach(async () => {
        page.setDefaultTimeout(30000);
        page.setDefaultNavigationTimeout(30000);
        await login.oxid(page);
    });

    test('iDeal Payment method Successfull', async () => {
        await paypalUtility.payWithiDeal(page);
    });

    test('Blik Payment method Successfull', async () => {
        await paypalUtility.payWithBlik(page);
    });

    test('Przelewy24 Payment method Successfull', async () => {
        await paypalUtility.payWithPrzelewy24(page);
    });

    test('EPS Payment method Successfull', async () => {
        await paypalUtility.payWithEPS(page);
    });

    test('Bancontact Payment method Successfull', async () => {
        await paypalUtility.payWithBancontact(page);
    });

    test.afterAll(async () => {
        await context.close();
    });
});
