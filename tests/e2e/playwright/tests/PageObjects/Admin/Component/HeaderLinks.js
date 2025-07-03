/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

export async function openShopsStartPage(page) {
    const shopsStartPageLink = "#shopfrontlink";

    await page.locator(shopsStartPageLink).click();

    // Switch to the newly opened tab
    const [newTab] = await page.context().pages();
    await newTab.waitForLoadState();
}
