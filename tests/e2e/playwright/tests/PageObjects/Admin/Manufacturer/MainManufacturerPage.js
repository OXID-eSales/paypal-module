import { Manufacturer } from './Manufacturer'; // Assuming Manufacturer class is in a separate file
import { Page } from './Page'; // Assuming Page class is in a separate file
import { ManufacturerList } from './ManufacturerList'; // Assuming ManufacturerList trait or helper

export class MainManufacturerPage extends Page {
    activeInput = "//input[@name='editval[oxmanufacturers__oxactive]']";
    titleInput = "//input[@name='editval[oxmanufacturers__oxtitle]']";
    shortDescriptionInput = "//input[@name='editval[oxmanufacturers__oxshortdesc]']";
    sortValueInput = "//input[@name='editval[oxmanufacturers__oxsort]']";
    saveButton = "//input[@name='saveArticle']";

    async editManufacturer(manufacturer) {
        const { user } = this;

        if (manufacturer.isActive()) {
            await user.checkOption(this.activeInput);
        } else {
            await user.uncheckOption(this.activeInput);
        }

        await user.fillField(this.titleInput, manufacturer.getTitle());
        await user.fillField(this.shortDescriptionInput, manufacturer.getShortDescription());
        await user.fillField(this.sortValueInput, manufacturer.getSortValue());
        await user.click(this.saveButton);
        await user.waitForDocumentReadyState();

        return this;
    }

    async seeManufacturer(manufacturer) {
        const { user } = this;

        await user.seeInField(this.activeInput, manufacturer.isActive());
        await user.seeInField(this.titleInput, manufacturer.getTitle());
        await user.seeInField(this.shortDescriptionInput, manufacturer.getShortDescription());
        await user.seeInField(this.sortValueInput, String(manufacturer.getSortValue()));

        return this;
    }
}
