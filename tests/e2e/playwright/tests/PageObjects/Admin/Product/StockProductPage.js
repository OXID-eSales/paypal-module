import { Page } from './Page'; // Assuming Page is in a separate file

export class StockProductPage extends Page {
    saveButton = "//input[@name='save']";
    lowStockMessageOption = 'editval[oxarticles__oxlowstockactive]';
    lowStockMessage = 'editval[oxarticles__oxlowstocktext]';
    remindAmount = 'editval[oxarticles__oxremindamount]';

    async setLowStockMessageValue(message) {
        const { user } = this;
        await user.fillField(this.lowStockMessage, message);

        return this;
    }

    async seeLowStockMessageValue(message) {
        const { user } = this;
        await user.seeInField(this.lowStockMessage, message);

        return this;
    }

    async checkLowStockMessageOption() {
        const { user } = this;
        await user.checkOption(this.lowStockMessageOption);

        return this;
    }

    async uncheckLowStockMessageOption() {
        const { user } = this;
        await user.uncheckOption(this.lowStockMessageOption);

        return this;
    }

    async seeLowStockMessageSelected() {
        const { user } = this;
        await user.seeCheckboxIsChecked(this.lowStockMessageOption);

        return this;
    }

    async dontSeeLowStockMessageSelected() {
        const { user } = this;
        await user.dontSeeCheckboxIsChecked(this.lowStockMessageOption);

        return this;
    }

    async setRemindAmountValue(remindAmount) {
        const { user } = this;
        await user.fillField(this.remindAmount, remindAmount);

        return this;
    }

    async seeRemindAmountValue(remindAmount) {
        const { user } = this;
        await user.seeInField(this.remindAmount, String(remindAmount));

        return this;
    }

    async save() {
        const { user } = this;
        await user.selectEditFrame();
        await user.click(this.saveButton);
        await user.waitForPageLoad();

        return this;
    }
}
