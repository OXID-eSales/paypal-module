import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { Page } from './Page'; // Assuming Page is in a separate file

export class GenericImport extends Page {
    attachCsvFileInput = 'input[name=csvfile]';
    targetTableSelector = 'select[name=sType]';
    csvEnclosureInput = 'input[name="sGiCsvFieldEncloser"]';
    csvTerminatorInput = 'input[name="sGiCsvFieldTerminator"]';
    useFirstLineAsCsvHeaderInput = 'input[name=blContainsHeader]';
    proceedToNextStepButton = "//input[@type='submit'][contains(@value, '%s')]";
    csvColumnToFieldMappingSelector = 'table.genImportFieldsAssign > tbody > tr:nth-child(%d) select';

    async setTargetTable(tableName) {
        const { user } = this;
        await user.selectOption(this.targetTableSelector, tableName);
        return this;
    }

    async setCsvSourceFile(csvFilePath) {
        const { user } = this;
        await user.attachFile(this.attachCsvFileInput, csvFilePath);
        return this;
    }

    async setCsvFieldTerminator(fieldTerminator) {
        const { user } = this;
        await user.fillField(this.csvTerminatorInput, fieldTerminator);
        return this;
    }

    async setCsvFieldEnclosure(enclosure) {
        const { user } = this;
        await user.fillField(this.csvEnclosureInput, enclosure);
        return this;
    }

    async setFirstCsvRowContainsHeaders() {
        const { user } = this;
        await user.checkOption(this.useFirstLineAsCsvHeaderInput);
        return this;
    }

    async proceedToFieldMapping(tableName) {
        const { user } = this;
        await user.click(sprintf(this.proceedToNextStepButton, await Translator.translate('Upload file')));
        await user.waitForText(await Translator.translate('GENIMPORT_ASSIGNFIELDS'));
        await user.see(tableName);
        return this;
    }

    async setCsvColumnToFieldMapping(csvColumn, dbField) {
        const { user } = this;
        await user.selectOption(
            sprintf(this.csvColumnToFieldMappingSelector, csvColumn),
            dbField
        );
        return this;
    }

    async seeCsvColumnToFieldMapping(csvColumn, dbField) {
        const { user } = this;
        await user.seeOptionIsSelected(
            sprintf(this.csvColumnToFieldMappingSelector, csvColumn),
            dbField
        );
        return this;
    }

    async doImport() {
        const { user } = this;
        await user.click(sprintf(this.proceedToNextStepButton, await Translator.translate('Begin import')));
        await user.waitForText(await Translator.translate('GENIMPORT_IMPORTDONE'));
        return this;
    }
}
