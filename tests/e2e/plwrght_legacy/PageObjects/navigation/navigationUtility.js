import { test, expect } from '@playwright/test';
async function changeLanguageToEN(page) {
    await page.locator('.languages-menu').click();
    await page.locator('[title="English"]').click();

    // Find the specific 'li' element that contains 'English' and has the 'active' class
    const languageItem = await page.locator('li.dropdown-item.active').filter({ hasText: 'English' });

    // Verify if the element has the 'active' class (using a regex to match part of the class)
    // await expect(languageItem).toHaveClass(/active/);  // Use regex to match the "active" class
}

module.exports = { changeLanguageToEN };



