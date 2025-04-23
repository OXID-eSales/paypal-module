import { Page } from './Page'; // Assuming Page is in a separate file

export class MainProductPage extends Page {
    activeCheckbox = "//input[@name='editval[oxarticles__oxactive]'][@type='checkbox']";
    titleInput = "//input[@name='editval[oxarticles__oxtitle]']";
    numberInput = "//input[@name='editval[oxarticles__oxartnum]']";
    priceInput = "//input[@name='editval[oxarticles__oxprice]']";
    longDescriptionInput = '#editor_oxarticles__oxlongdesc';
    createButton = "//a[@id='btn.new']";
    saveButton = "//input[@name='saveArticle']";

    async create(title, number = null, price = null) {
        const { user } = this;

        await user.selectEditFrame();
        await user.click(this.createButton);

        // Wait for list and edit sections to load
        await user.selectListFrame();
        await user.selectEditFrame();

        await user.checkOption(this.activeCheckbox);
        await user.fillField(this.titleInput, title);

        if (number) {
            await user.fillField(this.numberInput, number);
        }

        if (price) {
            await user.fillField(this.priceInput, price);
        }

        await user.waitForElementClickable(this.saveButton);
        await user.click(this.saveButton);
        await user.selectEditFrame();
        await user.selectListFrame();

        return this;
    }

    async save() {
        const { user } = this;
        await user.selectEditFrame();
        await user.click(this.saveButton);
        await user.waitForPageLoad();
        return this;
    }
}
