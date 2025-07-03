export async function selectStaticSeoUrl(page, option) {
    const staticSeoUrlSelect = '//select[@name="aStaticUrl[oxseo__oxobjectid]"]';

    await page.locator(staticSeoUrlSelect).selectOption({ label: option });

    return this;
}

export async function seeInStaticSeoUrlFields(page, standardUrl, germanUrl, englishUrl) {
    const standardUrlInput = 'aStaticUrl[oxseo__oxstdurl]';
    const localizedUrlInput = 'aStaticUrl[oxseo__oxseourl]';

    await page.locator(standardUrlInput).waitFor();
    await page.locator(standardUrlInput).evaluate((el, text) => el.value === text, standardUrl);

    await page.locator(localizedUrlInput.replace('%s', '0')).evaluate((el, text) => el.value === text, germanUrl);
    await page.locator(localizedUrlInput.replace('%s', '1')).evaluate((el, text) => el.value === text, englishUrl);

    return this;
}

export async function fillStaticSeoUrlFields(page, germanUrl, englishUrl) {
    const localizedUrlInput = 'aStaticUrl[oxseo__oxseourl]';

    await page.locator(localizedUrlInput.replace('%s', '0')).fill(germanUrl);
    await page.locator(localizedUrlInput.replace('%s', '1')).fill(englishUrl);

    return this;
}

export async function save(page) {
    await page.locator('button[type="submit"]').click();
    await page.waitForLoadState('domcontentloaded');

    return this;
}
