import * as paypalUtility from '../../PageObjects/paypal/paypalUtility';
import * as login from '../../PageObjects/login/login';
import { test, expect } from '@playwright/test';

test.describe('Non German Payment Methods Tests Failed&Canceled', () => {

    let page;
    let context;

    test.beforeAll(async ({ browser }) => {
        // Create a new browser context and page
        context = await browser.newContext();
        page = await context.newPage();
    });

    test.beforeEach(async () => {
        // Perform the login before each test
        await login.oxid(page);
    });

    test('iDeal Payment method Failed', async () => {
        await paypalUtility.payWithiDealFailed(page);
    });

    test('iDeal Payment method Canceled', async () => {
        await paypalUtility.payWithiDealCanceled(page);
    });

    test('Blik Payment method Failed', async () => {
        await paypalUtility.payWithBlikFailed(page);
    });

    test('Blik Payment method Canceled', async () => {
        await paypalUtility.payWithBlikCanceled(page);
    });

    test('Przelewy24 Payment method Failed', async () => {
        await paypalUtility.payWithPrzelewy24Failed(page);
    });

    test('Przelewy24 Payment method Canceled', async () => {
        await paypalUtility.payWithPrzelewy24Canceled(page);
    });

    test('EPS Payment method Failed', async () => {
        await paypalUtility.payWithEPSFailed(page);
    });

    test('EPS Payment method Canceled', async () => {
        await paypalUtility.payWithEPSCanceled(page);
    });

    test('Bancontact Payment method Failed', async () => {
        await paypalUtility.payWithBancontactFailed(page);
    });

    test('Bancontact Payment method Canceled', async () => {
        await paypalUtility.payWithBancontactCanceled(page);
    });

    test.afterAll(async () => {
        // Close the context after all tests
        await context.close();
    });
});
