const navigateTo = require('../navigation/navigationUtility');
require('dotenv').config();


// Access environment variables
const shop_user = process.env.SHOP_USER;
const shop_user_password = process.env.SHOP_USER_PASSWORD;


async function oxid6(page) {
    await page.goto('/');

    // Wait for the page to load completely
    await page.waitForLoadState('load');  // Wait for page to fully load

//    await navigateTo.changeLanguageToEN(page);  // Assuming this is defined similarly in Playwright

    // Wait for the service menu to be clickable and click it
    await page.locator('.service-menu').waitFor({ state: 'visible' });
    await page.locator('.service-menu').click();

    // Wait for the email input to be visible and fill it
    await page.locator('#loginEmail').waitFor({ state: 'visible' });
    await page.locator('#loginEmail').fill(shop_user);

    // Wait for the password input field to be visible
    await page.locator('[type="password"]').waitFor({ state: 'visible' });
    await page.locator('[type="password"]').fill(shop_user_password);

    const loginButton = await page.locator('button[type="submit"]:has-text("Log in")'); // Use text matching
    await loginButton.click();
}

module.exports = { oxid6 };
