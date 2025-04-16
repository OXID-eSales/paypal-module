/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

import { AssignProductsPopup } from './AssignProductsPopup'; // Ensure this path is correct

export async function enableInheritRights(page) {
    const inheritRightsCheckbox = "//input[@name='editval[oxcategories__oxrootid]'][@type='checkbox']";
    const saveButton = "//input[@name='save']";

    await page.locator(inheritRightsCheckbox).check();
    await page.locator(saveButton).click();
    await page.waitForNavigation();
}

export async function disableInheritRights(page) {
    const inheritRightsCheckbox = "//input[@name='editval[oxcategories__oxrootid]'][@type='checkbox']";
    const saveButton = "//input[@name='save']";

    await page.locator(inheritRightsCheckbox).uncheck();
    await page.locator(saveButton).click();
    await page.waitForNavigation();
}

export async function assignUserRightsToCategory(page) {
    const assignVisibleRightsButton = "//input[@value='%s']";
    const assignButtonSelector = assignVisibleRightsButton.replace('%s', 'CATEGORY_RIGHTS_ASSIGNVISIBLE');

    await page.locator(assignButtonSelector).click();

    const [newTab] = await page.context().pages();
    await newTab.waitForLoadState();
    await newTab.locator('text=GENERAL_AJAX_ASSIGNALL').click();
    await newTab.waitForResponse(response => response.status() === 200);
    await newTab.close();
}
