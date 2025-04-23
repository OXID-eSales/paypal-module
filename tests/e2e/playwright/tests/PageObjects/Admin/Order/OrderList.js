import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { OrderOverviewPage } from './OrderOverviewPage'; // Assuming OrderOverviewPage is in a separate file
import { DownloadsOrderPage } from './DownloadsOrderPage'; // Assuming DownloadsOrderPage is in a separate file
import { AddressesOrderPage } from './AddressesOrderPage'; // Assuming AddressesOrderPage is in a separate file
import { ProductsOrderPage } from './ProductsOrderPage'; // Assuming ProductsOrderPage is in a separate file
import { MainOrderPage } from './MainOrderPage'; // Assuming MainOrderPage is in a separate file

export class OrderList {
    searchForm = '#search';
    orderNumberInput = 'where[oxorder][oxordernr]';
    orderBillingLastNameInput = 'where[oxorder][oxbilllname]';

    async findByOrderNumber(orderNumber) {
        return this.find(this.orderNumberInput, orderNumber);
    }

    async find(field, value) {
        const { user } = this;

        await user.selectListFrame();
        await user.fillField(field, value);
        await user.submitForm(this.searchForm);

        await user.selectListFrame();
        await user.click(value);

        await user.selectEditFrame();

        return new OrderOverviewPage(user);
    }

    async openDownloadsTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclorder_downloads'));
        await user.selectEditFrame();

        return new DownloadsOrderPage(user);
    }

    async openAddressesTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclorder_address'));
        await user.selectEditFrame();

        return new AddressesOrderPage(user);
    }

    async openProductsTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclorder_article'));
        await user.selectEditFrame();

        return new ProductsOrderPage(user);
    }

    async deleteOrder(columNumber = '1') {
        await this.executeListModifier(`#del.${columNumber}`);

        return new MainOrderPage(this.user);
    }

    async cancelOrder(columNumber = '1') {
        await this.executeListModifier(`#pau.${columNumber}`);

        return new MainOrderPage(this.user);
    }

    async executeListModifier(modifierId) {
        const { user } = this;

        await user.selectListFrame();
        await user.click(modifierId);
        await user.acceptPopup();
        await user.waitForDocumentReadyState();
    }
}
