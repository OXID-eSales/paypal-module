import { expect } from "@playwright/test";

export class PuiHelper {
    constructor(page) {
        if (!page) {
            throw new Error('Invalid or undefined "page" provided to PuiHelper.');
        }

        this.page = page;
    }

    async fillPuiForm() {
        const { page } = this;

        console.log('Starting to fill PayPal Pay Upon Invoice form...');
        // Wait a moment for form to render
        await page.waitForSelector('.panel-body')
        // --- Trigger validation messages to appear (as in your example) ---
        // --- Birthday: Day ---
        await page.click('#pui_required_birthdate_day');
        await page.fill('#pui_required_birthdate_day', '22');
        console.log('Filled day: 22');
        // --- Birthday: Month (optional; already selected in DOM) ---
        const monthSelect = page.locator('#pui_required_birthdate_month');
        if (await monthSelect.isVisible()) {
            await monthSelect.selectOption({ label: 'November' });
            console.log('Selected month: November');
        } else {
            console.log('Month select not found or already selected.');
        }
        // --- Birthday: Year ---
        await page.click('#pui_required_birthdate_year');
        await page.fill('#pui_required_birthdate_year', '1998');
        console.log('Filled year: 1998');
        // --- Phone number ---
        await page.click('#pui_required_phonenumber');
        await page.fill('#pui_required_phonenumber', '+4930123456789');
        console.log('Filled phone number: +4930123456789');
        // Short wait to stabilize DOM
        await page.waitForTimeout(500);
        // --- Verification (optional, logs filled values) ---
        const values = {
            day: await page.inputValue('#pui_required_birthdate_day'),
            month: await page.inputValue('#pui_required_birthdate_month'),
            year: await page.inputValue('#pui_required_birthdate_year'),
            phone: await page.inputValue('#pui_required_phonenumber'),
        };
        console.log('PUI form values after fill:', values);
        if (values.day && values.year && values.phone) {
            console.log('✅ PUI form filled successfully.');
        } else {
            console.log('⚠️ One or more PUI fields may be empty.');
        }
    }

}
