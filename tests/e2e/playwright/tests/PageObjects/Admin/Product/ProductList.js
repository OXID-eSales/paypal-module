import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { MainProductPage } from './MainProductPage'; // Assuming MainProductPage is in a separate file
import { ExtendedInformationPage } from './ExtendedInformationPage'; // Assuming ExtendedInformationPage is in a separate file
import { SelectionProductPage } from './SelectionProductPage'; // Assuming SelectionProductPage is in a separate file
import { VariantsProductPage } from './VariantsProductPage'; // Assuming VariantsProductPage is in a separate file
import { DownloadsProductPage } from './DownloadsProductPage'; // Assuming DownloadsProductPage is in a separate file
import { StockProductPage } from './StockProductPage'; // Assuming StockProductPage is in a separate file

export class ProductList {
    searchNumberInput = "//input[@name='where[oxarticles][oxartnum]']";
    languageSelect = "//select[@name='changelang']";
    searchForm = '#search';
    productStatusClass = "//tr[@id='row.1']/td";

    async switchLanguage(language) {
        const { user } = this;

        await user.selectListFrame();
        await user.selectOption(this.languageSelect, language);
        await user.seeOptionIsSelected(this.languageSelect, language);
        await user.selectListFrame();
        await user.selectEditFrame();

        return new MainProductPage(user);
    }

    async filterByProductNumber(value) {
        const { user } = this;

        await user.selectListFrame();
        await user.fillField(this.searchNumberInput, value);
        await user.submitForm(this.searchForm);

        await user.selectListFrame();

        return this;
    }

    async find(field, value) {
        const { user } = this;

        await user.selectListFrame();
        await user.fillField(field, value);
        await user.submitForm(this.searchForm);

        await user.selectListFrame();
        await user.click(value);

        return this.openMainTab();
    }

    async openMainTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclarticle_main'));
        await user.selectEditFrame();
        await user.waitForDocumentReadyState();

        return new MainProductPage(user);
    }

    async openExtendedTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclarticle_extend'));
        await user.selectEditFrame();
        await user.waitForDocumentReadyState();

        return new ExtendedInformationPage(user);
    }

    async openSelectionTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclarticle_attribute'));
        await user.selectEditFrame();

        return new SelectionProductPage(user);
    }

    async openVariantsTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclarticle_variant'));
        await user.selectEditFrame();

        return new VariantsProductPage(user);
    }

    async openDownloadsTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclarticle_files'));
        await user.selectListFrame();
        await user.selectEditFrame();

        return new DownloadsProductPage(user);
    }

    async openStockTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclarticle_stock'));
        await user.selectListFrame();
        await user.selectEditFrame();

        return new StockProductPage(user);
    }
}
