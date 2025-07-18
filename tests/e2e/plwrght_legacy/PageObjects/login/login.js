const dotenv = require('dotenv');
const testData = require('../../../testData/user-address.json'); // Load static registration test data

dotenv.config(); // Load environment variables from .env

const SHOP_USER_EMAIL = process.env.SHOP_USER_EMAIL;
const SHOP_USER_PASSWORD = process.env.SHOP_USER_PASSWORD;

/**
 * Handles user login or registration flow in the e-shop.
 * @param {import('@playwright/test').Page} page - The Playwright page object.
 */
const loginOrRegisterUser = async (page) => {
    // Step 1: Click on the "Usercenter" button to open the dropdown/login section
    await page.locator('button[aria-label="Usercenter"]').click();

    // Step 2: Fill the login form
    await page.locator('form[name="login"] #loginEmail').fill(SHOP_USER_EMAIL);
    await page.locator('form[name="login"] #loginPasword').fill(SHOP_USER_PASSWORD);

    // Step 3: Attempt to log in
    await page.locator('form[name="login"] button[type="submit"]').click();

    // Step 4: Check for successful login or error
    try {
        // Define a selector for post-login validation (e.g., an element that appears only after login)
        await page.locator('text=Welcome Back').waitFor({ timeout: 5000 });
        console.log('Login successful.');
    } catch (error) {
        console.warn('Login failed. Attempting user registration...');

        // Step 5: Register the user if login fails
        await registerUser(page);
    }
};

/**
 * Registers a user if login fails.
 * @param {import('@playwright/test').Page} page - The Playwright page object.
 */
const registerUser = async (page) => {
    console.log('Starting user registration...');

    // Open the registration form
    await page.locator('a#registerLink').click();

    // Fill registration fields with data from testData
    await page.locator('#registerEmail').fill(SHOP_USER_EMAIL); // Email field
    await page.locator('#registerPassword').fill(SHOP_USER_PASSWORD); // Password field
    await page.locator('#firstName').fill(testData.firstName);
    await page.locator('#lastName').fill(testData.lastName);
    await page.locator('#address').fill(testData.address);
    await page.locator('#zip').fill(testData.zip);
    await page.locator('#city').fill(testData.city);
    await page.locator('#country').selectOption(testData.country); // Dropdown selection

    // Submit the registration form
    await page.locator('button[type="submit"]').click();

    // Validate registration success
    try {
        await page.locator('text=Thank you for registering').waitFor({ timeout: 5000 });
        console.log('User registration successful.');
    } catch (err) {
        console.error('Registration failed, please check the implementation or test data:', err);
    }
};

module.exports = { loginOrRegisterUser };
