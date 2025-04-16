export async function seeShopVersionInfo(page) {
    const versionUpdateInfoBlock = '#tVersionInfo';
    const versionCheckerResponseCurrentVersion = 'Your OXID eShop Version is';
    const versionCheckerResponseLatestVersion = 'Latest OXID eShop Version is';

    await page.locator(versionUpdateInfoBlock).waitFor();
    const currentVersion = await page.locator(versionUpdateInfoBlock).innerText();
    const latestVersion = await page.locator(versionUpdateInfoBlock).innerText();

    if (!currentVersion.includes(versionCheckerResponseCurrentVersion) || !latestVersion.includes(versionCheckerResponseLatestVersion)) {
        throw new Error('Version information not found');
    }

    return this;
}
