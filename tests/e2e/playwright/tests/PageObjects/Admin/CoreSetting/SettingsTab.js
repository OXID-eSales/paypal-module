export async function openDownloadableProducts(page) {
    const shopOptionsGroupDownloadableArticles = 'SHOP_OPTIONS_GROUP_SHOP_DOWNLOADABLEARTICLES';

    await page.locator(`text=${shopOptionsGroupDownloadableArticles}`).click();
    await page.locator('iframe').waitForLoadState();

    return this;
}

export async function openShopFrontendDropdown(page) {
    const shopOptionsGroupShopFrontend = 'SHOP_OPTIONS_GROUP_SHOP_FRONTEND';

    await page.locator(`text=${shopOptionsGroupShopFrontend}`).click();
    await page.locator('iframe').waitForLoadState();

    return this;
}

export async function openStartCategoryPopup(page) {
    await page.locator("//input[@value='---']").click();

    const [newTab] = await page.context().pages();
    await newTab.waitForSelector('.yui-dt-data');

    return new StartCategoryFrontendPopup(newTab);
}

export async function openAdministration(page) {
    const shopOptionsGroupAdministration = 'SHOP_OPTIONS_GROUP_ADMINISTRATION';

    await page.locator(`text=${shopOptionsGroupAdministration}`).click();
    await page.waitForLoadState();

    return this;
}

export async function openStockSettings(page) {
    const shopOptionsGroupStock = 'SHOP_OPTIONS_GROUP_STOCK';

    await page.locator(`text=${shopOptionsGroupStock}`).click();
    await page.locator('iframe').waitForLoadState();

    return new StockSettings(page);
}

export async function openAdditionalSettings(page) {
    const shopOptionsGroupOtherSettings = 'SHOP_OPTIONS_GROUP_OTHER_SETTINGS';

    await page.locator(`text=${shopOptionsGroupOtherSettings}`).click();
    await page.locator('iframe').waitForLoadState();

    return this;
}

export async function setAdminFormat(page, format) {
    await page.locator('select[name="confstrs[sLocalDateFormat]"]').selectOption({ label: format });
    const selectedOption = await page.locator('select[name="confstrs[sLocalDateFormat]"]').inputValue();
    if (selectedOption !== format) {
        throw new Error(`Expected format ${format}, but got ${selectedOption}`);
    }

    await page.waitForLoadState();

    return this;
}

export async function save(page) {
    await page.locator('button[type="submit"]').click();
    await page.waitForLoadState();

    return this;
}
