import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { Page } from './Page'; // Assuming Page is in a separate file

export class ProductsOrderPage extends Page {
    searchFieldInProductTab = 'sSearchArtNum';
    searchButtonInProductTab = '//input[@name="search"]';
    addButtonInProductTab = 'add';
    secondProductInProductTab = '#art.2';
    orderProductLabel = '#art.%d td:nth-of-type(5)';

    async addANewProductToTheOrder(articleNumber) {
        const { user } = this;

        await user.fillField(this.searchFieldInProductTab, articleNumber);
        await user.click(this.searchButtonInProductTab);
        await user.click(this.addButtonInProductTab);

        return this;
    }

    async seeOrderProductLabel(label, product) {
        const { user } = this;
        await user.see(
            `${await Translator.translate('GENERAL_LABEL')}: ${label}`,
            this.orderProductLabel.replace('%d', product)
        );
        return this;
    }

    async dontSeeOrderProductHasLabel(product) {
        const { user } = this;
        await user.dontSee(
            await Translator.translate('GENERAL_LABEL'),
            this.orderProductLabel.replace('%d', product)
        );
        return this;
    }
}
