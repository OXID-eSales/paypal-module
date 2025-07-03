/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

import { SortProductsPopup } from './SortProductsPopup'; // Ensure this path is correct

export async function openSortingProductsPopup(page) {
    const sortProductsButton = "//input[@value='%s']";
    const sortProductsButtonSelector = sortProductsButton.replace('%s', 'CATEGORY_ORDER_SORTCATEGORIES');

    await page.locator(sortProductsButtonSelector).click();

    const [newTab] = await page.context().pages();
    await newTab.waitForLoadState();
    await newTab.waitForResponse(response => response.status() === 200);

    return new SortProductsPopup(newTab);
}
