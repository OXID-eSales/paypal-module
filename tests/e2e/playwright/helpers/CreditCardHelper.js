import { expect } from "@playwright/test";

export class CreditCardHelper {
    constructor(page) {
        if (!page) {
            throw new Error('Invalid or undefined "page" or "context" provided to CreditCardHelper.');
        }

        this.page = page;

    }

    async addNewCard(selectors) {
        const {page} = this; // Access page from the instance variable

        // 1) Wait for the card container to be visible
        await expect(page.locator('#card_container')).toBeVisible();

        // Wait for 5 seconds before starting to fill in the information
        await page.waitForTimeout(5000);

        // 2) Card Number
        const numberFrame = page.frameLocator('#card-number-field-container iframe[title="paypal_card_number_field"]');
        const numberInput = numberFrame.locator('input, [contenteditable="true"]').first();
        await expect(numberInput).toBeVisible();
        await numberInput.fill('4020 0201 0112 3947');
        console.log('Filled card number');

        // Check if card number is correct
        const cardNumberValue = await numberInput.inputValue();
        console.log('Card Number value after fill:', cardNumberValue);
        if (cardNumberValue === '4020 0201 0112 3947') {
            console.log('Card Number is correct');
        } else {
            console.log('Card Number is incorrect');
        }

        // 5) CVV
        const cvvFrame = page.frameLocator('#card-cvv-field-container iframe[title="paypal_card_cvv_field"]');
        const cvvInput = cvvFrame.locator('input, [contenteditable="true"]').first();
        await expect(cvvInput).toBeVisible();
        await cvvInput.fill('123');
        console.log('Filled CVV');

        // Check if CVV is correct
        const cvvValue = await cvvInput.inputValue();
        console.log('CVV value after fill:', cvvValue);
        if (cvvValue === '123') {
            console.log('CVV is correct');
        } else {
            console.log('CVV is incorrect');
        }

        // 6) Cardholder Name
        const nameFrame = page.frameLocator('#card-name-field-container iframe[title="paypal_card_name_field"]');
        const nameInput = nameFrame.locator('input, [contenteditable="true"]').first();
        await expect(nameInput).toBeVisible();
        await nameInput.fill('John Playwright');
        console.log('Filled cardholder name');

        // Check if cardholder name is correct
        const nameValue = await nameInput.inputValue();
        console.log('Cardholder Name value after fill:', nameValue);
        if (nameValue === 'John Playwright') {
            console.log('Cardholder Name is correct');
        } else {
            console.log('Cardholder Name is incorrect');
        }

        // 3) Wait for 5 seconds before filling in the expiry date
        await page.waitForTimeout(2000);  // Adding 2 seconds wait before filling in expiry date

        // 4) Expiry Date with formatting
        const expiryFrame = page.frameLocator('#card-expiry-field-container iframe[title="paypal_card_expiry_field"]');
        const expiryInput = expiryFrame.locator('input, [contenteditable="true"]').first();
        await expect(expiryInput).toBeVisible();

        // Custom function to type expiry date manually
        const expiryDate = '03 / 33';

// Focus on the expiry input field first and clear it
        await expiryInput.click();
        // await expiryInput.clear(); // Clear any pre-filled values

// Wait for a moment to ensure the field is ready
        await page.waitForTimeout(500);

// Type the first part of the expiry date (03)
//         await expiryInput.type('03', { delay: 500 });
//
//         await page.waitForTimeout(500);
        await expiryInput.click();

// Type the second part of the expiry date (33)
        await expiryInput.type('33333', { delay: 500 });

// Check the value after typing
        const expiryValueAfterFill = await expiryInput.inputValue();
        console.log('Filled expiry date:', expiryDate);
        console.log('Expiry Date value after fill:', expiryValueAfterFill);

        if (expiryValueAfterFill === expiryDate) {
            console.log('Expiry Date is correct');
        } else {
            console.log('Expiry Date is incorrect');
        }


    }


























    async acceptCookiesAndSubmitOtp() {
        const { page } = this;

        // Wait for the first iframe (PayPal Checkout Overlay) to be attached
        const iframeElement = await page.waitForSelector('iframe[title="PayPal Checkout Overlay"]', { state: 'attached' });

        if (iframeElement) {
            // Get the content of the first iframe
            const iframe = await iframeElement.contentFrame();

            // Example: Get the title of the iframe
            const title = await iframe.title();
            console.log('Iframe Title:', title);

            // Now, look for the second iframe inside the first iframe (title="three_domain_secure")
            const secondIframeElement = await iframe.waitForSelector('iframe[title="three_domain_secure"]', { state: 'attached' });

            if (secondIframeElement) {
                // Get the content of the second iframe
                const secondIframe = await secondIframeElement.contentFrame();

                // You can now interact with the second iframe
                const secondIframeTitle = await secondIframe.title();
                console.log('Second Iframe Title:', secondIframeTitle);

                // Access the <body> of the second iframe
                const bodyElement = await secondIframe.$('body');

                if (bodyElement) {
                    console.log('Body element found inside the second iframe');

                    // Now, locate the accept button inside the <body> and click it
                    const acceptButton = await secondIframe.$('#acceptAllButton');

                    if (acceptButton) {
                        console.log('Accept button found, clicking it...');
                        await acceptButton.click();  // Click the button to accept cookies
                    } else {
                        console.log('Accept button not found.');
                    }

                    // Wait for the 4th iframe to load (directly from the second iframe)
                    try {
                        const fourthIframeElement = await secondIframe.waitForSelector('iframe#threedsIframeV2', { state: 'attached', timeout: 10000 });
                        if (fourthIframeElement) {
                            const fourthIframe = await fourthIframeElement.contentFrame();
                            console.log('Accessing the fourth iframe...');

                            // Wait for the 5th iframe to load inside the fourth iframe
                            try {
                                const fifthIframeElement = await fourthIframe.waitForSelector('iframe#threedsIframe', { state: 'attached', timeout: 10000 });
                                if (fifthIframeElement) {
                                    const fifthIframe = await fifthIframeElement.contentFrame();
                                    console.log('Accessing the fifth iframe...');

                                    // Now, interact with the form inside the fifth iframe
                                    const otpInput = await fifthIframe.$('input#otp');
                                    if (otpInput) {
                                        console.log('Entering OTP...');
                                        await otpInput.fill('1234');  // Fill the OTP input with '1234'
                                    } else {
                                        console.log('OTP input field not found.');
                                    }

                                    // Locate the submit button and click it
                                    const submitButton = await fifthIframe.$('input#submit-button');
                                    if (submitButton) {
                                        console.log('Submit button found, clicking it...');
                                        await submitButton.click();  // Click the submit button
                                    } else {
                                        console.log('Submit button not found.');
                                    }
                                } else {
                                    console.log('Fifth iframe not found!');
                                }
                            } catch (error) {
                                console.log('Error waiting for fifth iframe:', error);
                            }
                        } else {
                            console.log('Fourth iframe not found!');
                        }
                    } catch (error) {
                        console.log('Error waiting for fourth iframe:', error);
                    }
                } else {
                    console.log('Body element not found inside the second iframe.');
                }
            } else {
                console.log('Second iframe not found!');
            }
        } else {
            console.log('First iframe (PayPal Checkout Overlay) not found!');
        }
    }

}
