export async function selectCategory(page, categoryName) {
    const categoryNameSearchFilter = "//input[@name='_0']";
    const datTableFirstRow = "div#container1_c > table > tbody.yui-dt-data > tr:first-child";
    const dateTableSelectedRow = '.yui-dt-selected';
    const defaultCategoryLabelContainer = 'td#_defcat';

    await page.locator(categoryNameSearchFilter).fill(categoryName);
    await page.locator(datTableFirstRow + dateTableSelectedRow).waitFor({ state: 'hidden' });
    await page.locator(datTableFirstRow).waitFor({ state: 'visible' });
    await page.locator(datTableFirstRow).click();
    await page.locator(datTableFirstRow + dateTableSelectedRow).waitFor({ state: 'visible' });

    const assignDefaultCatButton = await page.locator('text=SHOP_CONFIG_ASSIGNDEFAULTCAT');
    await assignDefaultCatButton.click();

    await page.waitForLoadState('domcontentloaded');
    await page.locator(defaultCategoryLabelContainer).waitFor({ state: 'visible' });
    await page.locator(`text=${getDefaultCategoryLabel(categoryName)}`).waitFor({ state: 'visible' });

    return this;
}

export async function unsetCategory(page) {
    const defaultCategoryLabelContainer = 'td#_defcat';

    await page.locator(defaultCategoryLabelContainer).waitFor({ state: 'visible' });
    const unassignDefaultCatButton = await page.locator('text=SHOP_CONFIG_UNASSIGNDEFAULTCAT');
    await unassignDefaultCatButton.click();

    await page.waitForLoadState('domcontentloaded');
    await page.locator(defaultCategoryLabelContainer).waitFor({ state: 'hidden' });

    return this;
}

function getDefaultCategoryLabel(categoryName) {
    return `SHOP_CONFIG_ASSIGNEDDEFAULTCAT: ${categoryName}`;
}
