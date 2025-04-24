// file: tests/e2e/playwright/tests/PageObjects/paypal/paypalUtility.js


import * as utility from '../utility';  // Importing all functions from utility.js

import { test, expect } from '@playwright/test';


// let paypalCreds = {
//     email: process.env.PAYPAL_EMAIL,
//     password: process.env.PAYPAL_PASSWORD,
// };

export async function payWithApplePay(page) {
//    await utility.changeCountry(page, 'Germany');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.checkApplePayNotDisplayed(page);
}

export async function payWithGooglePay(page) {
//    await utility.changeCountry(page, 'Germany');
    await utility.addItemsToCart(page);
    // await utility.closeModal(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'GooglePay');
    await utility.nextStep(page);
    // const locator = await utility.clickGooglePay(page);
    // await page.waitForTimeout(3000); // wait 3 seconds (Playwright's way of waiting)
    await utility.handleGooglePayFlow(locator);
}


async function handlePayPalLogin(page, popup) {
    console.log('Starting PayPal login process');

    try {
        // Wait for the popup to be ready
        await popup.waitForLoadState('domcontentloaded');
        console.log('Popup loaded');

        // Wait for and fill email field
        const emailInput = popup.locator('#email');
        await emailInput.waitFor({ state: 'visible', timeout: 10000 });
        await emailInput.fill(process.env.PAYPAL_EMAIL);
        console.log('Email filled');

        // Click the Next button
        const nextButton = popup.locator('#btnNext');
        await nextButton.waitFor({ state: 'visible', timeout: 5000 });
        await nextButton.click();
        console.log('Clicked Next');

        // Wait for and fill password field
        const passwordInput = popup.locator('#password');
        await passwordInput.waitFor({ state: 'visible', timeout: 10000 });
        await passwordInput.fill(process.env.PAYPAL_PASSWORD);
        console.log('Password filled');

        // Click Login button
        const loginButton = popup.locator('#btnLogin');
        await loginButton.waitFor({ state: 'visible', timeout: 5000 });
        await loginButton.click();
        console.log('Clicked Login');

        // Wait for and click the final payment submit button if present
        try {
            const submitButton = popup.locator('[data-testid="submit-button-initial"]');
            await submitButton.waitFor({ state: 'visible', timeout: 10000 });
            await submitButton.click();
            console.log('Clicked Submit Payment');
        } catch (e) {
            console.log('Submit payment button not found or not needed');
        }

        // Wait for the popup to close or redirect
        await page.waitForTimeout(5000);

    } catch (error) {
        console.error('Error during PayPal login:', error);
        throw error;
    }
}


export async function payWithPaypal(page) {
    console.log('Starting PayPal payment flow');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethodPaypal(page, 'PayPal');
    await utility.nextStep(page);

    try {
        // Wait for PayPal button iframe to load
        await page.waitForTimeout(2000); // Give the page time to load PayPal elements

        // Find and click the PayPal button within its iframe
        const paypalButtonFrame = page.frameLocator('iframe[name^="__zoid__paypal_buttons__"]');
        const paypalButton = paypalButtonFrame.getByRole('link', { name: 'PayPal' });

        // Setup popup listener before clicking
        const popupPromise = page.waitForEvent('popup', { timeout: 10000 });

        // Click the PayPal button
        await paypalButton.click();
        console.log('Clicked PayPal button');

        // Now wait for the popup
        const popup = await popupPromise;
        console.log('PayPal popup detected');

        // Handle the login process
        await handlePayPalLogin(page, popup);

        // Wait for thank you page
        await utility.thankYouPage(page);

    } catch (error) {
        console.error('PayPal payment flow failed:', error);
        if (error.message.includes('timeout')) {
            console.log('Current page URL:', page.url());
            // Log all frames for debugging
            const frames = page.frames();
            console.log('Available frames:', frames.map(f => ({
                url: f.url(),
                name: f.name()
            })));
        }
        throw error;
    }
}


export async function payWithPaypalPayLater(page) {
//    await utility.changeCountry(page, 'Germany');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'PayPal- pay later');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.loginPaypalSession(page);
    await utility.paypalPaylaterFinishOrder(page);
    await utility.finishPaypalOrder(page);
    await utility.thankYouPage(page);
}

export async function payWithPayUponInvoice(page) {
    // await utility.changeCountry(page, 'Germany');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'Pay upon Invoice');
    await utility.nextStep(page);
    await utility.birtthDatePoneNr(page);
    await utility.orderNow(page);
}

export async function payWithSepa(page) {
    await utility.changeCountry(page, 'Germany');
    await utility.addItemsToCart(page);
    // await utility.closeModal(page);
}

export async function payWithPaypalExpress(page) {
    await utility.addItemsToCart(page);
    // await utility.closeModal(page);
    await utility.checkoutPaypalExpress(page);
    // await utility.thankYouPage(page);
}

export async function payWithCreditCards(page) {
    await utility.changeCountry(page, 'Germany');
    await utility.addItemsToCart(page);
    // await utility.closeModal(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'Credit Cards');
    await utility.nextStep(page);
    await utility.orderNow(page);
}

export async function payWithiDeal(page) {
    await utility.changeCountry(page, 'Netherlands');
    await utility.changeCurrency(page, 'EUR');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'iDEAL');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPage(page);
    await utility.thankYouPage(page);
}

export async function payWithiDealFailed(page) {
    await utility.changeCountry(page, 'Netherlands');
    await utility.changeCurrency(page, 'EUR');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'iDEAL');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPageFailed(page);
    await utility.assertFailedPayment(page);
}

export async function payWithiDealCanceled(page) {
    await utility.changeCountry(page, 'Netherlands');
    await utility.changeCurrency(page, 'EUR');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'iDEAL');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPageCanceled(page);
    await utility.assertFailedPayment(page);
}

export async function payWithBlik(page) {
    await utility.changeCountry(page, 'Poland');
    await utility.changeCurrency(page, 'PLN');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'BLIK');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPage(page);
    await utility.thankYouPage(page);
}

export async function payWithBlikFailed(page) {
    await utility.changeCountry(page, 'Poland');
    await utility.changeCurrency(page, 'PLN');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'BLIK');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPageFailed(page);
    await utility.assertFailedPayment(page);
}

export async function payWithBlikCanceled(page) {
    await utility.changeCountry(page, 'Poland');
    await utility.changeCurrency(page, 'PLN');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'BLIK');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPageCanceled(page);
    await utility.assertFailedPayment(page);
}

export async function payWithPrzelewy24(page) {
    await utility.changeCountry(page, 'Poland');
    await utility.changeCurrency(page, 'PLN');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'Przelewy24');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPage(page);
    await utility.thankYouPage(page);
}

export async function payWithPrzelewy24Failed(page) {
    await utility.changeCountry(page, 'Poland');
    await utility.changeCurrency(page, 'PLN');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'Przelewy24');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPageFailed(page);
    await utility.assertFailedPayment(page);
}

export async function payWithPrzelewy24Canceled(page) {
    await utility.changeCountry(page, 'Poland');
    await utility.changeCurrency(page, 'PLN');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'Przelewy24');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPageCanceled(page);
    await utility.assertFailedPayment(page);
}

export async function payWithEPS(page) {
    await utility.changeCountry(page, 'Austria');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'EPS');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPage(page);
    await utility.thankYouPage(page);
}

export async function payWithEPSFailed(page) {
    await utility.changeCountry(page, 'Austria');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'EPS');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPageFailed(page);
    await utility.assertFailedPayment(page);
}

export async function payWithEPSCanceled(page) {
    await utility.changeCountry(page, 'Austria');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'EPS');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPageCanceled(page);
    await utility.assertFailedPayment(page);
}

export async function payWithBancontact(page) {
    await utility.changeCountry(page, 'Belgium');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'Bancontact');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPage(page);
    await utility.thankYouPage(page);
}

export async function payWithBancontactFailed(page) {
    await utility.changeCountry(page, 'Belgium');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'Bancontact');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPageFailed(page);
    await utility.assertFailedPayment(page);
}

export async function payWithBancontactCanceled(page) {
    await utility.changeCountry(page, 'Belgium');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethod(page, 'Bancontact');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.nonGermanExternalPageCanceled(page);
    await utility.assertFailedPayment(page);
}





// // This function will handle the full PayPal checkout flow
// export async function executePayPalCheckout(page, context, paypalCreds, screenshotDir) {
//     const BASE_URL = process.env.BASE_URL || 'http://localhost';
//     const productPage = BASE_URL + '/Bekleidung/Fashion/Accessoires/Kuyichi-Lederguertel-JEVER.html';

//     await page.goto(productPage, { timeout: 5000 });
//     await page.screenshot({ path: path.join(screenshotDir, 'initial-page.png') });

//     const paypalIframeSelectors = [
//         'iframe[title="PayPal"]',
//         'iframe[title*="PayPal"]'
//     ];

//     let paypalFrameSelector = await utility.getPaypalFrameSelector(page, paypalIframeSelectors);

//     if (!paypalFrameSelector) {
//         throw new Error('PayPal iframe not found');
//     }

//     await page.waitForTimeout(3000);

//     const initialPages = context.pages();
//     const clicked = await page.evaluate((selector) => {
//         const iframe = document.querySelector(selector);
//         if (!iframe || !iframe.contentDocument) return false;
//         const buttons = iframe.contentDocument.querySelectorAll('[data-funding-source="paypal"], [role="button"], .paypal-button, button');
//         if (buttons.length > 0) {
//             buttons[0].click();
//             return true;
//         }
//         return false;
//     }, paypalFrameSelector);

//     if (clicked) {
//         await page.waitForTimeout(5000);
//         const afterClickPages = context.pages();
//         const newPages = afterClickPages.filter(p => !initialPages.includes(p));

//         if (newPages.length > 0) {
//             const popupPage = newPages[0];
//             await popupPage.waitForLoadState('domcontentloaded');
//             await popupPage.screenshot({ path: path.join(screenshotDir, 'paypal-popup.png'), fullPage: true });
//             await utility.handlePayPalPopup(popupPage, paypalCreds, screenshotDir);
//             await utility.paypalExressIsStillVisibleAndFunctionable(page, paypalIframeSelectors);
//         } else if (page.url().includes('paypal.com')) {
//             await utility.handlePayPalInMainWindow(page, paypalCreds);
//         } else {
//             throw new Error('PayPal button clicked but no popup or redirect occurred');
//         }
//     }
// }
