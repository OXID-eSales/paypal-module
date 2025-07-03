/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

export async function loadForm(page, loadTrigger, editableTextInput) {
    const randomValue = `random-${Math.random().toString(36).substring(2, 15)}`;

    console.log('Entering some arbitrary data into text input of the current form');

    // Wait for the element to be visible
    await page.locator(editableTextInput).waitFor();

    // Fill the input field with a random value
    await page.locator(editableTextInput).fill(randomValue);

    // Retry and verify that the value is filled
    await page.locator(editableTextInput).fill(randomValue);

    // Click on the load trigger
    await page.locator(loadTrigger).click();

    console.log('Expecting the form to be replaced with a new one after the request finishes');

    // Wait for the document to be ready and the new form to load
    await page.waitForLoadState('domcontentloaded');

    // Wait for the editable text input to be visible again
    await page.locator(editableTextInput).waitFor();

    // Verify that the old value is not in the field anymore
    const currentValue = await page.locator(editableTextInput).inputValue();
    if (currentValue === randomValue) {
        throw new Error(`Field still contains the previous value: ${randomValue}`);
    }
}
