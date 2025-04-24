import { Page } from './Page'; // Assuming Page is in a separate file

export class SystemInfo extends Page {
    dateTableHeader = "//a[@name = '%s']/following::table[1]/tbody/tr[contains(td[@class='e'], '%s')]/td[@class='v']";

    async setRowInDateTable(directive, vale) {
        await this.seeTableRowWithDirectiveValuePair('module_date', directive, vale);
        return this;
    }

    async seeTableRowWithDirectiveValuePair(module, directive, vale) {
        const { user } = this;
        await user.selectBaseFrame();
        const selector = sprintf(this.dateTableHeader, module, directive);
        await user.see(vale, selector);
    }
}
