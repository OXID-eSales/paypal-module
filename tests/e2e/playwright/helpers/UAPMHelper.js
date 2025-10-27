// helper/UAPMHelper.js
import { expect } from '@playwright/test';

export class UAPMHelper {
    constructor(page) {
        if (!page) {
            throw new Error('Invalid or undefined "page" provided to UAPMHelper.');
        }
        this.page = page;
    }

    // ---------------------------
    // Internal generic clicker
    // ---------------------------
    async _clickStatusTestButton({ id, testid, humanLabel }) {
        const { page } = this;
        // Prefer data-testid, then id, then name
        const selector = `[data-testid="${testid}"], #${id}, button[name="${id}"]`;
        const button = page.locator(selector).first();

        const count = await button.count();
        console.log(`[status-test] Looking for "${humanLabel}" using selector "${selector}" → found: ${count}`);

        if (!count) {
            throw new Error(`[status-test] Button not found for "${humanLabel}"`);
        }

        const visible = await button.isVisible().catch(() => false);
        console.log(`[status-test] "${humanLabel}" visible: ${visible}`);

        if (!visible) {
            console.log(`[status-test] Scrolling "${humanLabel}" into view…`);
        }
        await button.scrollIntoViewIfNeeded().catch(() => {});

        try {
            console.log(`[status-test] Clicking "${humanLabel}"…`);
            await button.click();
        } catch (err) {
            console.warn(`[status-test] Normal click failed on "${humanLabel}": ${err?.message}. Retrying with JS click…`);
            const handle = await button.elementHandle();
            if (!handle) throw new Error(`[status-test] Could not get element handle for "${humanLabel}"`);
            await page.evaluate(el => el.click(), handle);
        }

        console.log(`[status-test] Clicked "${humanLabel}" successfully.`);
        // Optional settle time if the page needs to react
        await page.waitForTimeout(500);
    }

    async clickTestSuccessfulPayment() {
        return this._clickStatusTestButton({
            id: 'Successful',
            testid: 'Successful',
            humanLabel: 'Test Successful Payment',
        });
    }

    async clickTestFailedPayment() {
        return this._clickStatusTestButton({
            id: 'Failed',
            testid: 'Failed',
            humanLabel: 'Test Failed Payment',
        });
    }

    async clickTestCancelledPayment() {
        return this._clickStatusTestButton({
            id: 'Canceled', // note: HTML shows id="Canceled"
            testid: 'Canceled',
            humanLabel: 'Test Cancelled Payment',
        });
    }

    async checkPaymentRedirect() {
        await this.page.waitForLoadState('load');

        const url = this.page.url();
        console.log('Redirected to:', url);

        const lower = url.toLowerCase();
        if (lower.includes('paypal.com')) {
            expect(url).toContain('paypal.com');
        } else if (lower.includes('ideal')) {
            expect(lower).toContain('ideal');
        } else {
            throw new Error(`Unexpected redirect URL: ${url}`);
        }
        return url;
    }

    async _statusAlert({ type = 'success', text, timeout = 10000 } = {}) {
        const typeFrag = `ppvx_alert--type_${type}_`;
        const baseSel = `div[role="alert"][class*="${typeFrag}"]`;
        const findIn = async (root) => {
            const loc = root.locator(baseSel).filter({ hasText: text || '' }).first();
            return (await loc.count()) ? loc : null;
        };

        const deadline = Date.now() + timeout;
        let target = null;
        // Poll page + iframes until timeout
        while (Date.now() < deadline) {
            target = await findIn(this.page);

            if (!target) {
                for (const f of this.page.frames()) {
                    target = await findIn(f);
                    if (target) break;
                }
            }
            if (target) break;
            await this.page.waitForTimeout(200);
        }
        // If we still didn't find anything, throw a crisp error (avoid expect(null))
        if (!target) {
            const wanted = text instanceof RegExp ? text.toString() : JSON.stringify(text || '');
            throw new Error(`Status alert not found within ${timeout}ms. selector=${baseSel} text=${wanted}`);
        }
        const remaining = Math.max(0, deadline - Date.now());
        await expect(target, 'Status alert should be visible').toBeVisible({ timeout: remaining });

        if (text) {
            if (text instanceof RegExp) {
                await expect(target).toHaveText(text, { timeout: remaining });
            } else {
                await expect(target).toContainText(text, { timeout: remaining });
            }
        }

        return target;
    }


    async verifyFailed(timeout = 10000) {
        // Handles smart quote in "You’ve" and both Canceled/Cancelled
        const text = /You.?ve tested (?:Failed|Failed) payment/i;
        await this._statusAlert({ type: 'success', text, timeout });
    }

    async verifyCanceled(timeout = 10000) {
        // Handles smart quote in "You’ve" and both Canceled/Cancelled
        const text = /You.?ve tested (?:Canceled|Canceled) payment/i;
        await this._statusAlert({ type: 'success', text, timeout });
    }

    async verifyAuthorizationFailed(timeout = 10000) {
        const selector = 'div.alert.alert-danger';
        const text = /The payment authorization failed\.?\s+Please verify your input!?/i;

        const findIn = async (root) => {
            const loc = root.locator(selector).filter({ hasText: text }).first();
            return (await loc.count()) ? loc : null;
        };

        let target = await findIn(this.page);
        if (!target) {
            for (const f of this.page.frames()) {
                target = await findIn(f);
                if (target) break;
            }
        }

        if (!target) {
            throw new Error('Danger alert not found (page + iframes)');
        }

        await expect(target).toBeVisible({ timeout });
        await expect(target).toContainText(text, { timeout });
    }
}
