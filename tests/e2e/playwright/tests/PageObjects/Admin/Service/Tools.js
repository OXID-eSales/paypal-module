import { Page } from './Page'; // Assuming Page is in a separate file

export class Tools extends Page {
    sqlTextInput = '#myedit textarea[name="updatesql"]';
    uploadSqlFileInput = '#myedit input[name="myfile[SQL1@usqlfile]"]';
    runUpdateSqlButton = '#myedit input[name="save"]';
    updateDbViewsButton = '#regerateviews input.confinput';
    sqlOutputElement = '.editnavigation';

    async updateDbViews() {
        const { user } = this;
        await user.selectEditFrame();
        await user.click(this.updateDbViewsButton);
        await user.retryAcceptPopup();
        await user.waitForDocumentReadyState();

        return this;
    }

    async runSqlUpdate(sqlCommand) {
        const { user } = this;
        await user.selectEditFrame();
        await user.fillField(this.sqlTextInput, sqlCommand);
        await user.click(this.runUpdateSqlButton);
        await user.waitForDocumentReadyState();

        return this;
    }

    async seeInSqlOutput(text) {
        const { user } = this;
        await user.selectListFrame();
        await user.see(text, this.sqlOutputElement);

        return this;
    }
}
