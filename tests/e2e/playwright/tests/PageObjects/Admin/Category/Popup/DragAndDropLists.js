/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

import { Page } from '@playwright/test';

export async function dragFromList1ToList2(page) {
    const list1 = '#container1';
    const list2 = '#container2';
    const firstListItem = '.yui-dt-data tr.yui-dt-first';

    const listItem = await page.locator(`${list1} ${firstListItem}`);
    await listItem.dragTo(page.locator(list2));
    await page.waitForResponse(response => response.status() === 200);
}

export async function dragFromList2ToList1(page) {
    const list1 = '#container1';
    const list2 = '#container2';
    const firstListItem = '.yui-dt-data tr.yui-dt-first';

    const listItem = await page.locator(`${list2} ${firstListItem}`);
    await listItem.dragTo(page.locator(list1));
    await page.waitForResponse(response => response.status() === 200);
}

export async function searchInList1(page, value) {
    const list1 = '#container1';
    const artNrSearchInput = 'input[name="_0"]';

    await page.fill(`${list1} ${artNrSearchInput}`, value);
    await page.locator(`${list1} ${artNrSearchInput}`).press('Enter');
    await page.waitForResponse(response => response.status() === 200);
}

export async function searchInList2(page, value) {
    const list2 = '#container2';
    const artNrSearchInput = 'input[name="_0"]';

    await page.fill(`${list2} ${artNrSearchInput}`, value);
    await page.locator(`${list2} ${artNrSearchInput}`).press('Enter');
    await page.waitForResponse(response => response.status() === 200);
}

export async function clearSearch(page, listSelector) {
    const artNrSearchInput = 'input[name="_0"]';
    const searchField = `${listSelector} ${artNrSearchInput}`;

    await page.fill(searchField, '');
    await page.locator(searchField).press('Backspace');
    await page.waitForResponse(response => response.status() === 200);
}

export async function seeProductInUnassignedList(page, artNr) {
    const list1 = '#container1';
    await expect(page.locator(list1)).toContainText(artNr);
}

export async function dontSeeProductInUnassignedList(page, artNr) {
    const list1 = '#container1';
    await expect(page.locator(list1)).not.toContainText(artNr);
}

export async function seeProductInAssignedList(page, artNr) {
    const list2 = '#container2';
    await expect(page.locator(list2)).toContainText(artNr);
}

export async function dontSeeProductInAssignedList(page, artNr) {
    const list2 = '#container2';
    await expect(page.locator(list2)).not.toContainText(artNr);
}

export async function assignProductByArtNr(page, artNr) {
    await searchInList1(page, artNr);
    await dragFromList1ToList2(page);
    await clearSearch(page, '#container1');
}

export async function unassignProductByArtNr(page, artNr) {
    await searchInList2(page, artNr);
    await dragFromList2ToList1(page);
    await clearSearch(page, '#container2');
}

export async function assignAllProducts(page) {
    const assignAllButton = '#container1_btn';
    await page.click(assignAllButton);
    await page.waitForResponse(response => response.status() === 200);
}

export async function unassignAllProducts(page) {
    const unassignAllButton = '#container2_btn';
    await page.click(unassignAllButton);
    await page.waitForResponse(response => response.status() === 200);
}
