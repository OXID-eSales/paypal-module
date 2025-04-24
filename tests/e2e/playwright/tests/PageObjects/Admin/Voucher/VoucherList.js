import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { MainVoucherPage } from './MainVoucherPage'; // Assuming MainVoucherPage is in a separate file

export class VoucherList {
    titleField = 'where[oxvoucherseries][oxserienr]';
    searchForm = '#search';

    async findByTitle(value) {
        const { user } = this;

        await user.selectListFrame();
        await user.fillField(this.titleField, value);
        await user.submitForm(this.searchForm);

        await user.selectListFrame();
        await user.click(value);

        return this.openMainTab();
    }

    async openMainTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclvoucherserie_main'));
        await user.selectEditFrame();
        await user.waitForDocumentReadyState();

        return new MainVoucherPage(user);
    }
}
