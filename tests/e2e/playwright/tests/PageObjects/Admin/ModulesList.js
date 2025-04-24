import { Page } from './Page'; // Assuming Page is in a separate file

export class ModulesList extends Page {
    moduleInformation = '#transfer';
    moduleTabSelector = '//div[@class="tabs"]//a[text()="%s"]';
    activateModuleButton = '#module_activate';
    deactivateModuleButton = '#module_deactivate';

    async selectModule(moduleName) {
        const { user } = this;

        await user.selectListFrame();
        await user.waitForText(moduleName, 10);
        await user.click(moduleName);
        await user.selectEditFrame();
        await user.waitForElement(this.moduleInformation, 10);

        return this;
    }

    async openModuleTab(tab) {
        const { user } = this;

        await user.selectListFrame();
        const selector = this.moduleTabSelector.replace('%s', tab);
        await user.waitForElement(selector, 10);
        await user.click(selector);
        await user.selectEditFrame();
        await user.waitForElement(this.moduleInformation, 10);

        return this;
    }

    async activateModule(tab) {
        const { user } = this;

        await this.openModuleTab(tab);

        await user.dontSeeElement(this.deactivateModuleButton);
        await user.seeElement(this.activateModuleButton);
        await user.click(this.activateModuleButton);
        await user.waitForPageLoad();
        await user.dontSeeElement(this.activateModuleButton);
        await user.seeElement(this.deactivateModuleButton);

        return this;
    }

    async deactivateModule(tab) {
        const { user } = this;

        await this.openModuleTab(tab);

        await user.dontSeeElement(this.activateModuleButton);
        await user.seeElement(this.deactivateModuleButton);
        await user.click(this.deactivateModuleButton);
        await user.waitForPageLoad();
        await user.dontSeeElement(this.deactivateModuleButton);
        await user.seeElement(this.activateModuleButton);

        return this;
    }
}
