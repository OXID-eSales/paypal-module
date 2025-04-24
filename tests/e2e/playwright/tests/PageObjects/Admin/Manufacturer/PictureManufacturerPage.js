import { ManufacturerList } from './ManufacturerList'; // Assuming ManufacturerList is in a separate file
import { Manufacturer } from './Manufacturer'; // Assuming Manufacturer is in a separate file
import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { Page } from './Page'; // Assuming Page is in a separate file

export class PictureManufacturerPage extends Page {
    iconInput = "//input[@name='editval[oxmanufacturers__oxicon]']";
    iconFile = "//input[@name='myfile[MICO@oxmanufacturers__oxicon]']";

    async seeManufacturerIcon(manufacturer) {
        const { user } = this;

        await user.seeInField(this.iconInput, manufacturer.getIcon());

        return this;
    }

    async uploadIcon(manufacturer) {
        const { user } = this;

        await user.attachFile(this.iconFile, manufacturer.getIcon());
        await user.click(await Translator.translate('GENERAL_SAVE'));

        return this;
    }
}
