// file: tests/e2e/playwright/tests/PageObjects/utility.js

import {Frame} from "@playwright/test";

const redPink = 'rgb(248, 215, 218)';
const red = 'rgb(114, 28, 36)';
import { test, expect } from '@playwright/test';


export async function addItemsToCart(page) {
    await page.locator('.productData').nth(0).locator('[type="submit"]').click();
    await page.waitForTimeout(2000);
    await closeModal(page);
    // await page.locator('.productData').nth(1).locator('[type="submit"]').click();
    // await page.waitForTimeout(2000);
    // await closeModal(page);
    // await page.locator('.productData').nth(2).locator('[type="submit"]').click();
    // await page.waitForTimeout(2000);
    // await closeModal(page);
    // await page.locator('.minibasket-menu').filter({ hasText: 'Display cart' }).click();
    // await page.locator('.minibasket-menu-box').waitFor({ state: 'visible' });
    // await page.locator('.minibasket-menu-box').locator('text=Display cart').click();
    // await setValueAndCheck(page, '1', 1);
    // await setValueAndCheck(page, '2', 2);
    // await setValueAndCheck(page, '3', 3);
}

export async function setValueAndCheck(page, productSelector, productorder, counter = 0) {
    const value = await page.locator(`#am_${productSelector}`).inputValue();
    if (value !== '1') {
        await page.locator(`#am_${productSelector}`).fill('1');
        await page.locator(`#basketUpdate-${productorder}`).click();

        if (counter < 2) {
            await page.waitForTimeout(1000);
            await setValueAndCheck(page, counter + 1);
        } else {
            throw new Error('Failed to set the value after 3 attempts');
        }
    }
}

export async function checkApplePayNotDisplayed(page) {
    // You can handle this similarly with a try/catch block or log to the console
    page.on('console', (message) => {
        console.log(message.text());
        // if (!message.text().includes('This device does not support Apple Pay')) {
        //     throw new Error('Specific error message not detected: ' + message.text());
        // }
    });
}

export async function closeModal(page) {
    await page.locator('.modal-content .close').click();
}

export async function checkout(page) {
    await page.locator('.minibasket-menu').filter({ hasText: 'Checkout' }).click();
    await page.locator('.btn-primary').locator('text=Checkout').click();
    await page.waitForTimeout(2000);
}

export async function checkoutPaypalExpress(page) {
    await page.locator('.minibasket-menu').filter({ hasText: 'Checkout' }).click();
    await page.waitForTimeout(2000);
    // await page.frameLocator('iframe').locator('.paypal-button-container').click();
}

export async function selectPaymentMethodPaypal(page, context) {
    // Normalize the context to match the format of the 'id' attribute of the radio button (if needed)
    const normalizedContext = context.toLowerCase().replace(/\s+/g, '').replace('-', '').trim();

    // Locate the input element (radio button) using the 'value' or 'id' and click it
    await page.locator(`input[type="radio"][value="osc${normalizedContext}"]`).click();
}

export async function selectPaymentMethod(page, context) {
    // Locate the element with the text matching 'context' within the '#payment' element and click it
    await page.locator('#payment').locator(`text=${context}`).click();
}


export async function nextStep(page) {
    await page.locator('.nextStep').locator('text=Continue to the next step').click();
}

export async function orderNow(page) {
    const [popupPage] = await Promise.all([
        // page.waitForEvent('popup'),  // Wait for PayPal popup
        page.frameLocator('iframe[name^="__zoid__paypal_buttons__"]')
            .getByRole('link', { name: 'PayPal' })
            .click()
    ]);

    // After this, you can pass `popupPage` to your login function
    return popupPage;
}

export async function birtthDatePoneNr(page) {
    await page.locator('#pui_required_birthdate_day').fill('20');
    await page.locator('#pui_required_birthdate_month').selectOption({ label: 'November' });
    await page.locator('#pui_required_birthdate_year').fill('1980');
    await page.locator('#pui_required_phonenumber').fill('+49 30 123456789');
}

//export async function loginSandboxPaypal(page) {
//    await page.waitForTimeout(5000);
//    await page.locator('#email').clear(),
//        await page.locator('#email').fill('sb-6omgx1009248@personal.example.com'),
//        await page.locator('#password').clear(),
//        await page.locator('#password').fill('v8Q.H9T1'),
//        await page.locator('.actions #btnLogin').click(),
//
//        await page.locator('[data-testid="submit-button-initial"]').click();
//    await page.waitForTimeout(2000);
//}

//
// export async function loginPaypalSession(page) {
//     // Wait for login iframe to appear (usually has "paypal_checkout" in name)
//     const loginFrame = await waitForPaypalLoginIframe(page);
//     require('dotenv').config();
//     const email = process.env.PAYPAL_EMAIL;
//     const password = process.env.PAYPAL_PASSWORD;
//
//     // Fill email and click next
//     await loginFrame.locator('#email').fill(email);
//
//     // Fill password and login
//     await loginFrame.locator('#password').waitFor({ state: 'visible', timeout: 10000 });
//     await loginFrame.locator('#password').fill(password);
//     await loginFrame.locator('#btnLogin').click();
//     await page.waitForTimeout(2000);
//     await loginFrame.locator('#payment-submit-btn').click();
//
//     // Optionally wait for success or redirection
//     await page.waitForTimeout(3000);
// }

async function waitForPaypalLoginIframe(page) {
    const maxWait = 15000;
    const pollInterval = 500;
    let waited = 0;

    while (waited < maxWait) {
        const frames = page.frames();
        const loginFrame = frames.find(f => f.url().includes('paypal.com') && f.name().includes('checkout'));
        if (loginFrame) {
            return loginFrame;
        }
        await page.waitForTimeout(pollInterval);
        waited += pollInterval;
    }

    throw new Error('PayPal login iframe not found after waiting');
}


export async function clickGooglePay(page) {
    return await page.locator('[aria-label="Buy with GPay"]').click();
}

export async function changeCountry(page, context) {
//    await page.locator('.service-menu').click();
//    await page.locator('#services').locator('text=My account').click();
//    await page.locator('.list-group-item').locator('text=Billing and shipping addresses').click();
//    await page.locator('#userChangeAddress').click();
//    await page.locator('#invCountrySelect').selectOption({ label: context });
//    await page.locator('#accUserSaveTop').click();
//    await page.locator('#navigation').locator('text=Home').click();
//    await page.locator('#content').waitFor({ state: 'visible' });
}

export async function changeCurrency(page, context) {
    await page.locator('[aria-label="Currencys"]').click();
    await page.locator('.dropdown-item').locator(`text=${context}`).click();
}

export async function thankYouPage(page) {
    const text = await page.locator('#thankyouPage').textContent();
    expect(text).toContain('Thank you for ordering');
}

export async function paypalPaylaterFinishOrder(page) {
    await page.locator('.FiDetails_description_3nler').locator('text=PayPal Ratenzahlung').click();
    await page.locator('.CheckoutButton_wrapper_2km6O').locator('text=Weiter').click();
    await page.waitForTimeout(2000);
    await page.frameLocator('iframe').locator('#phoneNumber').fill('01577 7952010');
    await page.frameLocator('iframe').locator('.ppvx_checkbox input').click({ force: true });
    await page.frameLocator('iframe').locator('[data-testid="pageFooterButton"]').locator('text=Zustimmen und weiter').click();
}

export async function finishPaypalOrder(page) {
    await page.locator('#payment - submit - btn').click();
}

export async function nonGermanExternalPageFailed(page) {
    await page.locator('#Failed').click();
    await page.waitForTimeout(2000);
}

export async function nonGermanExternalPageCanceled(page) {
    await page.locator('#Canceled').click();
    await page.waitForTimeout(2000);
}

export async function assertFailedPayment(page) {
    const alert = await page.locator('.alert-danger');
    expect(await alert.textContent()).toContain('The payment authorization failed. Please verify your input!');
    expect(await alert.isVisible()).toBe(true);
    expect(await alert.evaluate(el => window.getComputedStyle(el).backgroundColor)).toBe(redPink);
    expect(await alert.evaluate(el => window.getComputedStyle(el).color)).toBe(red);
    expect(page.url()).toContain('oxid.academy');
}

export async function nonGermanExternalPage(page) {
    await page.locator('#Successful').click();
    await page.waitForTimeout(5000);
}

async function detectPayPalLoginContext(page) {
    const maxAttempts = 3;
    const attemptInterval = 2000; // 2 seconds between attempts

    for (let attempt = 1; attempt <= maxAttempts; attempt++) {
        console.log(`Attempt ${attempt}/${maxAttempts} to detect PayPal context`);

        // Check for popup first
        try {
            console.log('Checking for popup...');
            const popup = await page.waitForEvent('popup', { timeout: 3000 });
            if (popup) {
                console.log('Popup detected');
                await popup.waitForLoadState('domcontentloaded');
                return { type: 'popup', context: popup };
            }
        } catch (e) {
            console.log('No popup detected:', e.message);
        }

        // Then check for iframes
        try {
            console.log('Checking for iframes...');
            await page.waitForTimeout(1000); // Small delay to ensure frames are loaded

            const frames = page.frames();
            console.log(`Found ${frames.length} frames`);

            // Log all frame URLs and names for debugging
            frames.forEach((frame, index) => {
                console.log(`Frame ${index}:`, {
                    url: frame.url(),
                    name: frame.name()
                });
            });

            // Look for PayPal-specific iframes
            const paypalSelectors = [
                'iframe[name^="__zoid__paypal_buttons__"]',
                'iframe[name*="paypal"]',
                'iframe[title*="PayPal"]'
            ];

            for (const selector of paypalSelectors) {
                const frameElement = await page.$(selector);
                if (frameElement) {
                    const frame = await frameElement.contentFrame();
                    if (frame) {
                        console.log(`PayPal iframe found with selector: ${selector}`);
                        return { type: 'iframe', context: frame };
                    }
                }
            }

            // Traditional frame search
            const loginFrame = frames.find(f =>
                f.url().includes('paypal.com') ||
                f.name().includes('checkout') ||
                f.name().includes('login') ||
                f.name().includes('xcomponent') // Common PayPal frame identifier
            );

            if (loginFrame) {
                console.log('PayPal frame found through URL/name matching');
                return { type: 'iframe', context: loginFrame };
            }
        } catch (e) {
            console.log('Error during iframe detection:', e.message);
        }

        if (attempt < maxAttempts) {
            console.log(`Waiting ${attemptInterval}ms before next attempt...`);
            await page.waitForTimeout(attemptInterval);
        }
    }

    console.log('All attempts to detect PayPal context failed');
    throw new Error('Could not detect PayPal login context (neither popup nor iframe found)');
}

export async function loginPaypalSession(page) {
    // Wait a bit for the PayPal context to initialize
    await page.waitForTimeout(2000);

    // Detect whether we're dealing with a popup or iframe
    const { type, context } = await detectPayPalLoginContext(page);

    require('dotenv').config();
    const email = process.env.PAYPAL_EMAIL;
    const password = process.env.PAYPAL_PASSWORD;

    try {
        // Handle login based on context type
        if (type === 'popup') {
            await context.waitForLoadState('networkidle');
            await context.locator('#email').fill(email);
            await context.locator('#password').waitFor({ state: 'visible', timeout: 10000 });
            await context.locator('#password').fill(password);
            await context.locator('#btnLogin').click();
            await context.waitForTimeout(2000);
            await context.locator('#payment-submit-btn').click();
        } else {
            // iframe handling
            await context.locator('#email').fill(email);
            await context.locator('#password').waitFor({ state: 'visible', timeout: 10000 });
            await context.locator('#password').fill(password);
            await context.locator('#btnLogin').click();
            await page.waitForTimeout(2000);
            await context.locator('#payment-submit-btn').click();
        }

        // Wait for the payment process to complete
        await page.waitForTimeout(3000);

    } catch (error) {
        console.error(`PayPal login failed in ${type} mode:`, error);
        throw error;
    }
}


export async function handleGooglePayFlow(page) {
    const gpayBtn = page.locator('button[aria-label="Buy with GPay"]');
    await gpayBtn.waitFor({ state: 'visible', timeout: 15_000 });

    // 2) Set up popup listener _before_ clicking
    const [popup] = await Promise.all([
        page.waitForEvent('popup'),
        gpayBtn.click({ force: true }),
    ]);

    // 4) Drive the Google login flow in the popup
    await popup.waitForLoadState('domcontentloaded');
    await popup.fill('input[type="email"]', process.env.GOOGLE_EMAIL);
    await popup.click('button:has-text("Next")');
    await popup.fill('input[type="password"]', process.env.GOOGLE_PASSWORD);
    await popup.click('button:has-text("Next")');

    // 5) Wait for it to close/return
    await popup.waitForClose({ timeout: 30_000 });
}





// // Raw function to handle PayPal popup
// export async function handlePayPalPopup(popupPage, paypalCreds, screenshotDir) {
//     console.log('Handling PayPal in popup window');
//     try {
//         const cancelSelectors = [
//             '#cancelLink',
//             'a[href*="cancel"]',
//             'button:has-text("Cancel")',
//             'a:has-text("Cancel")',
//             '.cancelUrl',
//             '#cancel_return',
//             '[data-testid="cancel-button"]'
//         ];

//         let cancelFound = false;
//         for (const selector of cancelSelectors) {
//             const count = await popupPage.locator(selector).count();
//             if (count > 0) {
//                 await popupPage.locator(selector).first().click();
//                 cancelFound = true;
//                 break;
//             }
//         }

//         const emailField = popupPage.locator('#email');
//         if (await emailField.count() > 0) {
//             const email = paypalCreds['email'];
//             await emailField.fill(email);
//             await popupPage.waitForTimeout(5000);
//         }

//         const nextButton = popupPage.locator('#btnNext');
//         if (await nextButton.count() > 0) {
//             await nextButton.click();
//         }

//         await popupPage.waitForTimeout(5000);

//         const passwordField = popupPage.locator('#password');
//         if (await passwordField.count() > 0) {
//             await passwordField.fill(paypalCreds['password']);
//         }

//         const buttonLogin = popupPage.locator('button[id="btnLogin"]');
//         await buttonLogin.waitFor({ state: 'visible' });
//         await buttonLogin.click();

//         const continueButton = popupPage.locator('button:has-text("Continue to review")');
//         await continueButton.waitFor({ state: 'visible' });

//         await popupPage.close();
//     } catch (error) {
//         console.log('Error handling PayPal popup:', error.message);
//         throw error;
//     }
// }

// // Raw function to handle PayPal in the main window
// export async function handlePayPalInMainWindow(page, paypalCreds) {
//     console.log('Handling PayPal in main window');
//     try {
//         const cancelSelectors = [
//             '#cancelLink',
//             'a[href*="cancel"]',
//             'button:has-text("Cancel")',
//             'a:has-text("Cancel")',
//             '.cancelUrl',
//             '#cancel_return',
//             '[data-testid="cancel-button"]'
//         ];

//         let cancelFound = false;
//         for (const selector of cancelSelectors) {
//             const count = await page.locator(selector).count();
//             if (count > 0) {
//                 await page.locator(selector).first().click();
//                 cancelFound = true;
//                 break;
//             }
//         }

//         if (!cancelFound) {
//             await page.goBack();
//         }

//         await page.waitForTimeout(5000);
//         await page.waitForLoadState('networkidle', { timeout: 30000 });
//     } catch (error) {
//         console.log('Error handling PayPal in main window:', error.message);
//         throw error;
//     }
// }

// // Raw function to check if PayPal Express buttons are still visible and functional
// export async function paypalExressIsStillVisibleAndFunctionable(page, paypalIframeSelectors) {
//     const paypalButtonsSelector = await getPaypalFrameSelector(page, paypalIframeSelectors);

//     if (!paypalButtonsSelector) {
//         throw new Error('PayPal iframe not found');
//     }

//     const paypalButtons = page.locator(paypalButtonsSelector);
//     const buttonCount = await paypalButtons.count();
//     if (buttonCount === 0) {
//         throw new Error('PayPal Express buttons not found on the page');
//     }

//     for (let i = 0; i < buttonCount; i++) {
//         const button = paypalButtons.nth(i);
//         if (!(await button.isVisible()) || !(await button.isEnabled())) {
//             throw new Error(`PayPal button #${i + 1} is either not visible or not enabled`);
//         }
//     }
// }

// // Raw function to get the PayPal iframe selector
// export async function getPaypalFrameSelector(page, paypalIframeSelectors) {
//     let paypalFrameSelector = null;
//     for (const selector of paypalIframeSelectors) {
//         const frameCount = await page.locator(selector).count();
//         if (frameCount > 0) {
//             paypalFrameSelector = selector;
//             break;
//         }
//     }
//     return paypalFrameSelector;
// }
