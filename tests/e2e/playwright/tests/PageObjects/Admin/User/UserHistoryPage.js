import { Page } from './Page'; // Assuming Page is in a separate file

export class UserHistoryPage extends Page {
    historyTabRemarkSelect = "//select[@name='rem_oxid']";
    deleteRemark = "//input[@value='Delete']";
    remarktextSelector = "//textarea[@name='remarktext']";
    remarkField = 'remarktext';

    async deleteRemark() {
        const { user } = this;

        await user.selectEditFrame();
        await user.click(this.deleteRemark);
        await user.selectEditFrame();

        return this;
    }

    async selectUserRemark(listItem) {
        const { user } = this;

        await user.selectOption(this.historyTabRemarkSelect, listItem);
        await user.waitForElement(this.remarktextSelector);

        return this;
    }
}
