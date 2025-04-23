import { Page } from './Page'; // Assuming Page is in a separate file
import { Translator } from './Translator'; // Assuming Translator is in a separate file

export class DiagnosticsTool extends Page {
    startDiagnosticsButton = '#submitButton';

    async startDiagnostics() {
        const { user } = this;
        await user.click(this.startDiagnosticsButton);
        await user.waitForDocumentReadyState();

        return this;
    }

    async seeDiagnosticResults() {
        const { user } = this;
        await user.see(await Translator.translate('OXDIAG_RESULT_SUCCESSFUL'));

        return this;
    }
}
