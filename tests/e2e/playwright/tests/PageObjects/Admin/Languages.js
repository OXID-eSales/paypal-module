import { Page } from './Page'; // Assuming Page is in a separate file
import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { FrameLoader } from './FrameLoader'; // Assuming FrameLoader is a mixin

export class Languages extends Page {
    // Including the FrameLoader functionality
    constructor(user) {
        super(user);
        Object.assign(this, FrameLoader);
    }

    newLanguageButton = '#btn.new';
    activeCheckbox = "//input[@name='editval[active]'][@type='checkbox']";
    abbreviationField = "//input[@name='editval[abbr]']";
    nameField = "//input[@name='editval[desc]']";

    async createNewLanguage(abbreviation, name) {
        const { user } = this;

        await user.selectEditFrame();
        await this.loadForm(this.newLanguageButton, this.nameField);

        await user.amGoingTo('fill and submit the form');
        await user.checkOption(this.activeCheckbox);
        await user.fillField(this.abbreviationField, abbreviation);
        await user.fillField(this.nameField, name);
        await user.click(await Translator.translate('GENERAL_SAVE'));

        await user.expect('to see the new language in the list');
        await user.retrySelectListFrame();
        await user.waitForText(name);

        return this;
    }
}
