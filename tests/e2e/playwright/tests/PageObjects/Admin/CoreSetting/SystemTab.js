export async function openVariants(page) {
    const shopOptionsGroupVariants = 'SHOP_OPTIONS_GROUP_VARIANTS';

    await page.locator(`text=${shopOptionsGroupVariants}`).click();
    await page.locator('iframe').waitForLoadState();

    return this;
}

export async function checkParentProductAsBuyable(page) {
    const buyableParentCheckbox = "//input[@type='checkbox' and contains(@name, 'blVariantParentBuyable')]";

    await page.locator(buyableParentCheckbox).check();
    const saveButton = await page.locator('text=GENERAL_SAVE');
    await saveButton.click();
    await page.waitForLoadState('domcontentloaded');

    return this;
}

export async function disableParentProductAsBuyable(page) {
    const buyableParentCheckbox = "//input[@type='checkbox' and contains(@name, 'blVariantParentBuyable')]";

    await page.locator(buyableParentCheckbox).uncheck();
    const saveButton = await page.locator('text=GENERAL_SAVE');
    await saveButton.click();
    await page.waitForLoadState('domcontentloaded');

    return this;
}

export async function enableVariantsInAssignmentLists(page) {
    const displayVariantsCheckbox = "//input[@type='checkbox' and contains(@name, 'blVariantsSelection')]";

    await page.locator(displayVariantsCheckbox).check();
    const saveButton = await page.locator('text=GENERAL_SAVE');
    await saveButton.click();
    await page.waitForLoadState('domcontentloaded');

    return this;
}

export async function disableVariantsInAssignmentLists(page) {
    const displayVariantsCheckbox = "//input[@type='checkbox' and contains(@name, 'blVariantsSelection')]";

    await page.locator(displayVariantsCheckbox).uncheck();
    const saveButton = await page.locator('text=GENERAL_SAVE');
    await saveButton.click();
    await page.waitForLoadState('domcontentloaded');

    return this;
}
