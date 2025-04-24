import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { Page } from './Page'; // Assuming Page is in a separate file

export class GenericExport extends Page {
    selectCategoryInput = 'select[name="acat[]"]';
    startExportButton = "//input[@type='submit'][contains(@value, '%s')]";
    exportResultsFile = 'div.export a';

    async selectExportCategory(category) {
        const { user } = this;
        await user.selectGenericExportMainFrame();
        await user.selectOption(this.selectCategoryInput, category);
        return this;
    }

    async doExport() {
        const { user } = this;
        await user.selectGenericExportMainFrame();
        await user.click(sprintf(this.startExportButton, await Translator.translate('Start Export')));
        await user.selectGenericExportStatusFrame();
        await user.waitForText(await Translator.translate('AUCTMASTER_DO_EXPORTEND'));
        return this;
    }

    async seeInExportResultsFile(text) {
        const { user } = this;
        await user.selectGenericExportStatusFrame();
        const url = await user.grabAttributeFrom(this.exportResultsFile, 'href');
        await user.amOnUrl(url);
        await user.see(text);
        return this;
    }
}
