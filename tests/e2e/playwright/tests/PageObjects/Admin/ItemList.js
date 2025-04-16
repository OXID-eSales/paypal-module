import { Page } from './Page'; // Assuming Page is in a separate file
import { ItemListTab } from './ItemListTab'; // Assuming ItemListTab is in a separate file

export class ItemList extends Page {
    navigationInformation = '#transfer';
    createNewItemButton = '//div[@class="actions"]//a[@id="btn.new"]';

    async selectItem(itemName) {
        const { user } = this;

        await user.selectListFrame();
        await user.waitForText(itemName, 10);
        await user.click(itemName);
        await user.selectEditFrame();
        await user.waitForElement(this.navigationInformation, 10);

        return this;
    }

    async openItemTab(tabPage) {
        const { user } = this;

        await user.selectListFrame();
        await user.waitForElement(tabPage.getTabSelector(), 10);
        await user.executeJS(`document.evaluate("${tabPage.getTabSelector()}", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click()`);

        await user.selectEditFrame();
        await user.waitForElement(this.navigationInformation, 10);

        return tabPage;
    }

    async openCreateNewItem(tabPage) {
        const { user } = this;

        await user.selectEditFrame();
        await user.click(this.createNewItemButton);
        await user.waitForPageLoad();
        await user.waitForElement(this.navigationInformation, 10);

        return tabPage;
    }
}
