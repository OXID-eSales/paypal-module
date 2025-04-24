// fiöe: tests/e2e/playwright/tests/e2e/CheckoutFlows/germanpaymentMethods.spec.js

import * as login from '../../PageObjects/login/login';
import * as paypalUtility from '../../PageObjects/paypal/paypalUtility'; // Importing the utility file
import { test, expect } from '@playwright/test';

test.describe('Payment Methods Tests', () => {

    let page;
    let context;

    test.beforeAll(async ({ browser }) => {
        // Create a new browser context and page for isolation
        context = await browser.newContext();
        page = await context.newPage();
    });

    test.beforeEach(async () => {
        // Call the login functionality (assuming it's defined properly)
        await login.oxid6(page);
    });

    test('Apple Pay Payment method xxx', async () => {
        await paypalUtility.payWithApplePay(page);
    });

    test('Credit Cards Payment method xxx', async () => {
        await paypalUtility.payWithCreditCards(page);
    });

    test('Google Pay Payment method xxxx', async () => {
        await paypalUtility.payWithGooglePay(page);
    });

    test('Paypal Payment method', async () => {
        await paypalUtility.payWithPaypal(page);
    });


    // test('Cancel PayPal Express checkout flow', async ({ page, context }) => {
    //     try {
    //         // Calling the function from paypalUtility
    //         await paypalUtility.executePayPalCheckout(page, context, paypalCreds, screenshotDir);
    //         console.log('PayPal checkout flow test completed successfully');
    //     } catch (error) {
    //         console.log('Error during PayPal checkout test:', error.message);
    //         throw error;
    //     }
    // });

    test('Paypal Express Payment method', async () => {
        await paypalUtility.payWithPaypalExpress(page);
    });

    test('PayPal - pay later Payment method', async () => {
        await paypalUtility.payWithPaypalPayLater(page);
    });

    test('Pay upon Invoice Payment method', async () => {
        await paypalUtility.payWithPayUponInvoice(page);
    });

    test('SEPA Direct Debit Payment method xxx', async () => {
        await paypalUtility.payWithSepa(page);
    });

    test.afterAll(async () => {
        // Cleanup: close the context and page
        await context.close();
    });
});
