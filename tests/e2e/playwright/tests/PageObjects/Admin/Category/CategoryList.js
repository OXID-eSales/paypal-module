/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

import { expect } from '@playwright/test';

export async function switchLanguage(page, language) {
    const categoryLanguageSelect = "//select[@name='changelang']";

    await page.locator(categoryLanguageSelect).selectOption({ label: language });
    await expect(page.locator(categoryLanguageSelect)).toHaveValue(language);
    // Returning a new instance of MainCategoryPage
    return new MainCategoryPage(page);
}

export async function selectProductCategory(page, categoryName) {
    return await findCategory(page, categoryName);
}

async function findCategory(page, categoryName) {
    const categorySearchForm = '#search';
    const categoryTitleInput = "//input[@name='where[oxcategories][oxtitle]']";

    await page.locator(categoryTitleInput).fill(categoryName);
    await page.locator(categorySearchForm).press('Enter');
    await page.locator(categoryName).click();
    // Assuming MainCategoryPage has been defined correctly
    await page.waitForSelector('body'); // Ensure the page is ready

    return new MainCategoryPage(page);
}

export async function openMainTab(page) {
    const mainTabSelector = "//a[text()='Main']"; // Update based on actual translation
    await page.locator(mainTabSelector).click();
    await page.waitForSelector('body'); // Wait for the page to be ready

    return new MainCategoryPage(page);
}

export async function openSortingTab(page) {
    const tabText = 'Sorting'; // Update based on actual translation
    const tabSelector = `//div[contains(@class, 'tabs')]//a[contains(text(), '${tabText}')]`;

    await page.locator(tabSelector).click();
    await page.waitForSelector('body'); // Wait for the page to be ready

    return new SortingCategoryPage(page);
}

export async function openRightsTab(page) {
    const rightsTabSelector = "//a[text()='Rights']"; // Update based on actual translation
    await page.locator(rightsTabSelector).click();
    await page.waitForSelector('body'); // Wait for the page to be ready

    return new RightsCategoryPage(page);
}
