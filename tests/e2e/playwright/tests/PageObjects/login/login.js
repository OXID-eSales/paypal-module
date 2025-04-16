const navigateTo = require('../navigation/navigationUtility');

async function oxid6(page) {
    await page.goto('/');

    // Wait for the page to load completely
    await page.waitForLoadState('load');  // Wait for page to fully load

    await navigateTo.changeLanguageToEN(page);  // Assuming this is defined similarly in Playwright

    // Wait for the service menu to be clickable and click it
    await page.locator('.service-menu').waitFor({ state: 'visible' });
    await page.locator('.service-menu').click();

    // Wait for the email input to be visible and fill it
    await page.locator('#loginEmail').waitFor({ state: 'visible' });
    await page.locator('#loginEmail').fill('razvan.zerfas@betterqa.co');

    // Wait for the password input field to be visible
    await page.locator('[type="password"]').waitFor({ state: 'visible' });
    await page.locator('[type="password"]').fill('Test12345?');



    // Fill the password


    // Click the login button
    const loginButton = await page.locator('button[type="submit"]:has-text("Log in")'); // Use text matching
    await loginButton.click();
}

module.exports = { oxid6 };
