import { Page } from './Page'; // Assuming Page is in a separate file
import { Translator } from './Translator'; // Assuming Translator is in a separate file

export class CMSPages extends Page {
    newCMSButton = '#btn.new';
    activeCheckbox = 'editval[oxcontents__oxactive]';
    title = 'editval[oxcontents__oxtitle]';
    ident = 'editval[oxcontents__oxloadid]';
    content = 'oxcontents__oxcontent';
    searchForm = '#search';

    async createNewCMS(title, ident, content) {
        const { user } = this;

        await user.selectEditFrame();

        await user.click(this.newCMSButton);
        await user.wait(3);

        // Create new CMS
        await user.checkOption(this.activeCheckbox);
        await user.fillField(this.title, title);
        await user.fillField(this.ident, ident);
        await user.fillField(this.content, content);
        await user.click(await Translator.translate('GENERAL_SAVE'));
        await user.wait(3);

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
        await user.selectListFrame();
    }
}
