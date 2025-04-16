export async function enableSaveCart(page) {
    const disableSaveCartCheckbox = 'confbools[blPerfNoBasketSaving]';

    await page.locator(`input[name="${disableSaveCartCheckbox}"]`).uncheck();
    const isChecked = await page.locator(`input[name="${disableSaveCartCheckbox}"]`).isChecked();
    if (isChecked) {
        throw new Error('Checkbox is still checked');
    }

    return this;
}

export async function disableSaveCart(page) {
    const disableSaveCartCheckbox = 'confbools[blPerfNoBasketSaving]';

    await page.locator(`input[name="${disableSaveCartCheckbox}"]`).check();
    const isChecked = await page.locator(`input[name="${disableSaveCartCheckbox}"]`).isChecked();
    if (!isChecked) {
        throw new Error('Checkbox is not checked');
    }

    return this;
}

export async function save(page) {
    await page.locator('button[type="submit"]').click();
    await page.waitForLoadState('domcontentloaded');

    return this;
}
