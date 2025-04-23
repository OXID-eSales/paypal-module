// const utility = require('../utility');
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
    await utility.clickGooglePay(page);
    await page.waitForTimeout(3000); // wait 3 seconds (Playwright's way of waiting)
    await utility.loginByGoogleApi(page);
}

export async function payWithPaypal(page) {
//    await utility.changeCountry(page, 'Germany');
    console.log('PayPal');
    await utility.addItemsToCart(page);
    await utility.checkout(page);
    await utility.selectPaymentMethodPaypal(page, 'PayPal');
    await utility.nextStep(page);
    await utility.orderNow(page);
    await utility.loginPaypalSession(page);
    await utility.thankYouPage(page);
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
