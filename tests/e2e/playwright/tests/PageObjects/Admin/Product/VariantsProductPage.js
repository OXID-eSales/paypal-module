import { Page } from './Page'; // Assuming Page is in a separate file
import { MainProductPage } from './MainProductPage'; // Assuming MainProductPage is in a separate file

export class VariantsProductPage extends Page {
    editVariantButton = '#test_variant\\.\\d+ > td:nth-child(1) > a';

    async openEditProductVariant(variant) {
        const { user } = this;

        await user.click(sprintf(this.editVariantButton, variant));
        await user.waitForPageLoad();

        return new MainProductPage(user);
    }
}
