import { Page } from './Page'; // Assuming Page is in a separate file

export class ItemListTab extends Page {
    // The tabHref is now a protected property, accessible through getter methods
    tabHref = '';
    tabSelector = "//div[@class='tabs']//a[@href='%s']";

    // Getter for tabHref
    getTabHref() {
        return this.tabHref;
    }

    // Getter for tabSelector, formatted with the tabHref value
    getTabSelector() {
        return this.tabSelector.replace('%s', this.getTabHref());
    }
}
