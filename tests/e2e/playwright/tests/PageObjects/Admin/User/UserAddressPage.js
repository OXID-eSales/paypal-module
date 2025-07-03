import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { Page } from './Page'; // Assuming Page is in a separate file
import { AdminUserAddresses } from './AdminUserAddresses'; // Assuming AdminUserAddresses is in a separate file

export class UserAddressPage extends Page {
    deleteAddressInput = "//input[@value='Delete']";
    addressesTabAddressSelect = 'oxaddressid';
    addressTitleField = "//select[@name='editval[oxaddress__oxsal]']";
    addressFirstNameField = "//input[@name='editval[oxaddress__oxfname]']";
    addressLastNameField = "//input[@name='editval[oxaddress__oxlname]']";
    addressCompanyField = "//input[@name='editval[oxaddress__oxcompany]']";
    addressStreetField = "//input[@name='editval[oxaddress__oxstreet]']";
    addressStreetNumberField = "//input[@name='editval[oxaddress__oxstreetnr]']";
    addressZipCodeField = "//input[@name='editval[oxaddress__oxzip]']";
    addressCityField = "//input[@name='editval[oxaddress__oxcity]']";
    addressAdditionalInformationField = "//input[@name='editval[oxaddress__oxaddinfo]']";
    addressCountryIdField = "//select[@name='editval[oxaddress__oxcountryid]']";
    addressPhoneField = "//input[@name='editval[oxaddress__oxfon]']";
    addressFaxField = "//input[@name='editval[oxaddress__oxfax]']";

    async deleteSelectedAddress() {
        const { user } = this;

        await user.selectEditFrame();
        await user.click(this.deleteAddressInput);

        await user.selectEditFrame();
        return this;
    }

    async selectAddress(adminUserAddress) {
        const { user } = this;

        await user.selectOption(this.addressesTabAddressSelect, this.getAddressTitle(adminUserAddress));
        return this;
    }

    async editUserAddress(adminUserAddresses) {
        const { user } = this;

        await user.selectOption(this.addressTitleField, adminUserAddresses.getTitle());
        await user.fillField(this.addressFirstNameField, adminUserAddresses.getFirstName());
        await user.fillField(this.addressLastNameField, adminUserAddresses.getLastName());
        await user.fillField(this.addressCompanyField, adminUserAddresses.getCompany());
        await user.fillField(this.addressStreetField, adminUserAddresses.getStreet());
        await user.fillField(this.addressStreetNumberField, adminUserAddresses.getStreetNumber());
        await user.fillField(this.addressZipCodeField, adminUserAddresses.getZip());
        await user.fillField(this.addressCityField, adminUserAddresses.getCity());
        await user.fillField(this.addressAdditionalInformationField, adminUserAddresses.getAdditionalInfo());
        await user.selectOption(this.addressCountryIdField, adminUserAddresses.getCountryId());
        await user.fillField(this.addressPhoneField, adminUserAddresses.getPhone());
        await user.fillField(this.addressFaxField, adminUserAddresses.getFax());

        await user.click(await Translator.translate('GENERAL_SAVE'));
        await user.waitForDocumentReadyState();

        return this;
    }

    async seeAddressInformation(adminUserAddress) {
        const { user } = this;

        await user.seeOptionIsSelected(this.addressesTabAddressSelect, this.getAddressTitle(adminUserAddress));
        await user.seeOptionIsSelected(this.addressTitleField, adminUserAddress.getTitle());
        await user.seeInField(this.addressFirstNameField, adminUserAddress.getFirstName());
        await user.seeInField(this.addressLastNameField, adminUserAddress.getLastName());
        await user.seeInField(this.addressCompanyField, adminUserAddress.getCompany());
        await user.seeInField(this.addressStreetField, adminUserAddress.getStreet());
        await user.seeInField(this.addressStreetNumberField, adminUserAddress.getStreetNumber());
        await user.seeInField(this.addressZipCodeField, adminUserAddress.getZip());
        await user.seeInField(this.addressCityField, adminUserAddress.getCity());
        await user.seeOptionIsSelected(this.addressCountryIdField, adminUserAddress.getCountryId());
        await user.seeInField(this.addressAdditionalInformationField, adminUserAddress.getAdditionalInfo());
        await user.seeInField(this.addressPhoneField, adminUserAddress.getPhone());
        await user.seeInField(this.addressFaxField, adminUserAddress.getFax());

        return this;
    }

    getAddressTitle(adminUserAddress) {
        let title = '-';
        if (adminUserAddress.getFirstName()) {
            title = `${adminUserAddress.getFirstName()} ${adminUserAddress.getLastName()}, 
                     ${adminUserAddress.getStreet()}, 
                     ${adminUserAddress.getCity()}`;
        }
        return title;
    }
}
