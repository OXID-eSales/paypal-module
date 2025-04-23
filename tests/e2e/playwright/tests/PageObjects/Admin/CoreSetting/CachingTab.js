export async function openDataCache(page) {
    const dataCacheBlock = '//form[@id="myedit1"]//div[@class="groupExp"][1]/div/a';

    await page.locator(dataCacheBlock).click();

    return new DataCache(page);
}

export async function openContentCache(page) {
    const contentCacheBlock = '//form[@id="myedit1"]//div[@class="groupExp"][2]/div/a';

    await page.locator(contentCacheBlock).click();

    return new ContentCache(page);
}
