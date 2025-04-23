import { FrameLoader } from './FrameLoader'; // Assuming FrameLoader is in a separate file
import { Manufacturer } from './Manufacturer'; // Assuming Manufacturer is in a separate file
import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { MainManufacturerPage } from './MainManufacturerPage'; // Assuming MainManufacturerPage is in a separate file
import { PictureManufacturerPage } from './PictureManufacturerPage'; // Assuming PictureManufacturerPage is in a separate file

export class ManufacturerList {
    searchForm = '#search';
    titleSearchField = "where[oxmanufacturers][oxtitle]";
    newManufacturerButton = "#btn.new";
    firstRowName = '//tr[@id="row.1"]//td[2]//div//a';

    async find(searchField, value) {
        const { user } = this;

        await user.selectListFrame();
        await user.fillField(searchField, value);
        await user.submitForm(this.searchForm);

        await user.selectListFrame();
        await user.click(this.firstRowName);
        await user.selectEditFrame();

        return new MainManufacturerPage(user);
    }

    async findByManufacturerTitle(title) {
        return this.find(this.titleSearchField, title);
    }

    async openPictureTab(title) {
        const { user } = this;

        await this.find(this.titleSearchField, title);

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclmanufacturer_picture'));
        await user.selectEditFrame();

        return new PictureManufacturerPage(user);
    }

    async openMainTab(title) {
        const { user } = this;

        await this.find(this.titleSearchField, title);

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclmanufacturer_main'));
        await user.selectEditFrame();

        return new MainManufacturerPage(user);
    }

    async createManufacturer(manufacturer) {
        const { user } = this;
        const mainManufacturerPage = new MainManufacturerPage(user);

        await user.selectEditFrame();
        await this.loadForm(this.newManufacturerButton, mainManufacturerPage.titleInput);
        await mainManufacturerPage.editManufacturer(manufacturer);
        const pictureManufacturerPage = await this.openPictureTab(manufacturer.getTitle());
        await pictureManufacturerPage.uploadIcon(manufacturer);

        return this.openMainTab(manufacturer.getTitle());
    }
}
