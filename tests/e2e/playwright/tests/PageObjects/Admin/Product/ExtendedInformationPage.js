import { Page } from './Page'; // Assuming Page is in a separate file

export class ExtendedInformationPage extends Page {
    isProductConfigurableOption = "editval[oxarticles__oxisconfigurable]";
    saveProductButton = "//input[@name='save']";

    async enableProductCustomization() {
        const { user } = this;

        await user.checkOption(this.isProductConfigurableOption);
        await user.click(this.saveProductButton);
        await user.waitForDocumentReadyState();

        return this;
    }
}
