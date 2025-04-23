/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

import { AssignProductsPopup } from './AssignProductsPopup'; // Ensure this path is correct

export async function create(page, categoryName) {
    const newCategoryName = "//input[@name='editval[oxcategories__oxtitle]']";
    const activeCategoryCheckbox = "//input[@name='editval[oxcategories__oxactive]'][@type='checkbox']";
    const newItemButtonId = '#btn.new';

    await page.locator(newItemButtonId).click();
    await page.locator(activeCategoryCheckbox).check();
    await page.locator(newCategoryName).fill(categoryName);
    await save(page);
    await page.waitForSelector(`text=${categoryName}`);
}

export async function save(page) {
    const saveButton = "//input[@name='save']";
    await page.locator(saveButton).click();
    await page.waitForNavigation();
}

export async function uploadThumbnail(page, categoryThumbPath) {
    const newCategoryThumbFile = 'myfile[TC@oxcategories__oxthumb]';
    const input = await page.locator(newCategoryThumbFile);
    await input.setInputFiles(categoryThumbPath);
    await save(page);
}

export async function openAssignProductsPopup(page) {
    const assignProductsButton = "//input[@value='%s']";
    await page.locator(assignProductsButton.replace('%s', 'GENERAL_ASSIGNARTICLES')).click();
    const [newTab] = await page.context().pages();
    await newTab.waitForLoadState();
    return new AssignProductsPopup(newTab);
}
