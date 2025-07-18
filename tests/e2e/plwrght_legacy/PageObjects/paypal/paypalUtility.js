// const ShopHelper = require('../ShopHelper');
import * as ShopHelper from '../ShopHelper';  // Importing all functions from ShopHelper.js

import { test, expect } from '@playwright/test';


// let paypalCreds = {
//     email: process.env.PAYPAL_EMAIL,
//     password: process.env.PAYPAL_PASSWORD,
// };

export async function payWithApplePay(page) {
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.checkApplePayNotDisplayed(page);
}

export async function payWithGooglePay(page) {
    await ShopHelper.addItemsToCart(page);
    // await ShopHelper.closeModal(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'GooglePay');
    await ShopHelper.nextStep(page);
    await ShopHelper.clickGooglePay(page);
    await page.waitForTimeout(3000); // wait 3 seconds (Playwright's way of waiting)
    await ShopHelper.loginByGoogleApi(page);
}

export async function payWithPaypal(page) {
    await ShopHelper.changeCountry(page, 'Germany');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethodPaypal(page, 'PayPal');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.loginSandboxPaypal(page);
    await ShopHelper.thankYouPage(page);
}

export async function payWithPaypalPayLater(page) {
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'PayPal- pay later');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.loginSandboxPaypal(page);
    await ShopHelper.paypalPaylaterFinishOrder(page);
    await ShopHelper.finishPaypalOrder(page);
    await ShopHelper.thankYouPage(page);
}

export async function payWithPayUponInvoice(page) {
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'Pay upon Invoice');
    await ShopHelper.nextStep(page);
    await ShopHelper.birtthDatePoneNr(page);
    await ShopHelper.orderNow(page);
}

export async function payWithSepa(page) {
    await ShopHelper.addItemsToCart(page);
    // await ShopHelper.closeModal(page);
}

export async function payWithPaypalExpress(page) {
    await ShopHelper.addItemsToCart(page);
    // await ShopHelper.closeModal(page);
    await ShopHelper.checkoutPaypalExpress(page);
    // await ShopHelper.thankYouPage(page);
}

export async function payWithCreditCards(page) {
    await ShopHelper.addItemsToCart(page);
    // await ShopHelper.closeModal(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'Credit Cards');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
}

export async function payWithiDeal(page) {
    await ShopHelper.changeCountry(page, 'Netherlands');
    await ShopHelper.changeCurrency(page, 'EUR');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'iDEAL');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPage(page);
    await ShopHelper.thankYouPage(page);
}

export async function payWithiDealFailed(page) {
    await ShopHelper.changeCountry(page, 'Netherlands');
    await ShopHelper.changeCurrency(page, 'EUR');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'iDEAL');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPageFailed(page);
    await ShopHelper.assertFailedPayment(page);
}

export async function payWithiDealCanceled(page) {
    await ShopHelper.changeCountry(page, 'Netherlands');
    await ShopHelper.changeCurrency(page, 'EUR');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'iDEAL');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPageCanceled(page);
    await ShopHelper.assertFailedPayment(page);
}

export async function payWithBlik(page) {
    await ShopHelper.changeCountry(page, 'Poland');
    await ShopHelper.changeCurrency(page, 'PLN');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'BLIK');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPage(page);
    await ShopHelper.thankYouPage(page);
}

export async function payWithBlikFailed(page) {
    await ShopHelper.changeCountry(page, 'Poland');
    await ShopHelper.changeCurrency(page, 'PLN');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'BLIK');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPageFailed(page);
    await ShopHelper.assertFailedPayment(page);
}

export async function payWithBlikCanceled(page) {
    await ShopHelper.changeCountry(page, 'Poland');
    await ShopHelper.changeCurrency(page, 'PLN');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'BLIK');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPageCanceled(page);
    await ShopHelper.assertFailedPayment(page);
}

export async function payWithPrzelewy24(page) {
    await ShopHelper.changeCountry(page, 'Poland');
    await ShopHelper.changeCurrency(page, 'PLN');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'Przelewy24');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPage(page);
    await ShopHelper.thankYouPage(page);
}

export async function payWithPrzelewy24Failed(page) {
    await ShopHelper.changeCountry(page, 'Poland');
    await ShopHelper.changeCurrency(page, 'PLN');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'Przelewy24');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPageFailed(page);
    await ShopHelper.assertFailedPayment(page);
}

export async function payWithPrzelewy24Canceled(page) {
    await ShopHelper.changeCountry(page, 'Poland');
    await ShopHelper.changeCurrency(page, 'PLN');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'Przelewy24');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPageCanceled(page);
    await ShopHelper.assertFailedPayment(page);
}

export async function payWithEPS(page) {
    await ShopHelper.changeCountry(page, 'Austria');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'EPS');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPage(page);
    await ShopHelper.thankYouPage(page);
}

export async function payWithEPSFailed(page) {
    await ShopHelper.changeCountry(page, 'Austria');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'EPS');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPageFailed(page);
    await ShopHelper.assertFailedPayment(page);
}

export async function payWithEPSCanceled(page) {
    await ShopHelper.changeCountry(page, 'Austria');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'EPS');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPageCanceled(page);
    await ShopHelper.assertFailedPayment(page);
}

export async function payWithBancontact(page) {
    await ShopHelper.changeCountry(page, 'Belgium');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'Bancontact');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPage(page);
    await ShopHelper.thankYouPage(page);
}

export async function payWithBancontactFailed(page) {
    await ShopHelper.changeCountry(page, 'Belgium');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'Bancontact');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPageFailed(page);
    await ShopHelper.assertFailedPayment(page);
}

export async function payWithBancontactCanceled(page) {
    await ShopHelper.changeCountry(page, 'Belgium');
    await ShopHelper.addItemsToCart(page);
    await ShopHelper.checkout(page);
    await ShopHelper.selectPaymentMethod(page, 'Bancontact');
    await ShopHelper.nextStep(page);
    await ShopHelper.orderNow(page);
    await ShopHelper.nonGermanExternalPageCanceled(page);
    await ShopHelper.assertFailedPayment(page);
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

//     let paypalFrameSelector = await ShopHelper.getPaypalFrameSelector(page, paypalIframeSelectors);

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
//             await ShopHelper.handlePayPalPopup(popupPage, paypalCreds, screenshotDir);
//             await ShopHelper.paypalExressIsStillVisibleAndFunctionable(page, paypalIframeSelectors);
//         } else if (page.url().includes('paypal.com')) {
//             await ShopHelper.handlePayPalInMainWindow(page, paypalCreds);
//         } else {
//             throw new Error('PayPal button clicked but no popup or redirect occurred');
//         }
//     }
// }
