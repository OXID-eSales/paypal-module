import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { Page } from './Page'; // Assuming Page is in a separate file
import { AdminUser } from './AdminUser'; // Assuming AdminUser is in a separate file
import { AdminUserAddresses } from './AdminUserAddresses'; // Assuming AdminUserAddresses is in a separate file

export class MainUserPage extends Page {
    userActiveField = "//input[@name='editval[oxuser__oxactive]'][@type='checkbox']";
    usernameField = "//input[@name='editval[oxuser__oxusername]']";
    userCustomerNumberField = "//input[@name='editval[oxuser__oxcustnr]']";
    userTitleField = "//select[@name='editval[oxuser__oxsal]']";
    userFirstNameField = "//input[@name='editval[oxuser__oxfname]']";
    userLastNameField = "//input[@name='editval[oxuser__oxlname]']";
    userCompanyField = "//input[@name='editval[oxuser__oxcompany]']";
    userStreetField = "//input[@name='editval[oxuser__oxstreet]']";
    userStreetNumberField = "//input[@name='editval[oxuser__oxstreetnr]']";
    userZipCodeField = "//input[@name='editval[oxuser__oxzip]']";
    userCityField = "//input[@name='editval[oxuser__oxcity]']";
    userUstidField = "//input[@name='editval[oxuser__oxustid]']";
    userAdditionalInformationField = "//input[@name='editval[oxuser__oxaddinfo]']";
    userCountryIdField = "//select[@name='editval[oxuser__oxcountryid]']";
    userStateIdField = "//input[@name='editval[oxuser__oxstateid]']";
    userPhoneField = "//input[@name='editval[oxuser__oxfon]']";
    userFaxField = "//input[@name='editval[oxuser__oxfax]']";
    userBirthDayField = "//input[@name='editval[oxuser__oxbirthdate][day]']";
    userBirthMonthField = "//input[@name='editval[oxuser__oxbirthdate][month]']";
    userBirthYearField = "//input[@name='editval[oxuser__oxbirthdate][year]']";
    userPasswordField = 'newPassword';
    userRightsField = "//select[@name='editval[oxuser__oxrights]']";
    userHasPasswordSelector = '#myedit table tr:nth-child(17) td:nth-child(2)';

    async editUserInformation(adminUser, adminUserAddress) {
        const { user } = this;

        if (adminUser.getActive()) {
            await user.checkOption(this.userActiveField);
        } else {
            await user.uncheckOption(this.userActiveField);
        }

        await user.fillField(this.usernameField, adminUser.getUsername());
        await user.fillField(this.userCustomerNumberField, adminUser.getCustomerNumber());
        await user.selectOption(this.userTitleField, adminUserAddress.getTitle());
        await user.fillField(this.userFirstNameField, adminUserAddress.getFirstName());
        await user.fillField(this.userLastNameField, adminUserAddress.getLastName());
        await user.fillField(this.userCompanyField, adminUserAddress.getCompany());
        await user.fillField(this.userStreetField, adminUserAddress.getStreet());
        await user.fillField(this.userStreetNumberField, adminUserAddress.getStreetNumber());
        await user.fillField(this.userZipCodeField, adminUserAddress.getZip());
        await user.fillField(this.userCityField, adminUserAddress.getCity());
        await user.fillField(this.userUstidField, adminUser.getUstid());
        await user.fillField(this.userAdditionalInformationField, adminUserAddress.getAdditionalInfo());
        await user.selectOption(this.userCountryIdField, adminUserAddress.getCountryId());
        await user.fillField(this.userStateIdField, adminUserAddress.getStateId());
        await user.fillField(this.userPhoneField, adminUserAddress.getPhone());
        await user.fillField(this.userFaxField, adminUserAddress.getFax());
        await user.fillField(this.userBirthDayField, adminUser.getBirthday());
        await user.fillField(this.userBirthMonthField, adminUser.getBirthMonth());
        await user.fillField(this.userBirthYearField, adminUser.getBirthYear());
        await user.fillField(this.userPasswordField, adminUser.getPassword());
        await user.selectOption(this.userRightsField, adminUser.getUserRights());

        await user.click(await Translator.translate('GENERAL_SAVE'));
        await user.waitForDocumentReadyState();

        return this;
    }

    async seeUserInformation(adminUser, adminUserAddress) {
        const { user } = this;

        if (adminUser.getActive()) {
            await user.seeCheckboxIsChecked(this.userActiveField);
        } else {
            await user.dontSeeCheckboxIsChecked(this.userActiveField);
        }

        await user.seeOptionIsSelected(this.userRightsField, adminUser.getUserRights());
        await user.seeInField(this.usernameField, adminUser.getUsername());
        await user.seeInField(this.userCustomerNumberField, adminUser.getCustomerNumber());
        await user.seeOptionIsSelected(this.userTitleField, adminUserAddress.getTitle());
        await user.seeInField(this.userFirstNameField, adminUserAddress.getFirstName());
        await user.seeInField(this.userLastNameField, adminUserAddress.getLastName());
        await user.seeInField(this.userCompanyField, adminUserAddress.getCompany());
        await user.seeInField(this.userStreetField, adminUserAddress.getStreet());
        await user.seeInField(this.userStreetNumberField, adminUserAddress.getStreetNumber());
        await user.seeInField(this.userZipCodeField, adminUserAddress.getZip());
        await user.seeInField(this.userCityField, adminUserAddress.getCity());
        await user.seeInField(this.userUstidField, adminUser.getUstid());
        await user.seeInField(this.userAdditionalInformationField, adminUserAddress.getAdditionalInfo());
        await user.seeOptionIsSelected(this.userCountryIdField, adminUserAddress.getCountryId());
        await user.seeInField(this.userStateIdField, adminUserAddress.getStateId());
        await user.seeInField(this.userPhoneField, adminUserAddress.getPhone());
        await user.seeInField(this.userFaxField, adminUserAddress.getFax());
        await user.seeInField(this.userBirthDayField, adminUser.getBirthday());
        await user.seeInField(this.userBirthMonthField, adminUser.getBirthMonth());
        await user.seeInField(this.userBirthYearField, adminUser.getBirthYear());

        await this.checkUserPassword(user, adminUser.getPassword());
        return this;
    }

    async updatePassword(pass) {
        const { user } = this;
        await user.fillField(this.userPasswordField, pass);
        await user.click(await Translator.translate('GENERAL_SAVE'));
        await user.waitForDocumentReadyState();

        return this;
    }

    async updateUsername(username) {
        const { user } = this;
        await user.fillField(this.usernameField, username);
        await user.click(await Translator.translate('GENERAL_SAVE'));
        await user.waitForDocumentReadyState();

        return this;
    }

    async checkUserPassword(user, password) {
        const passwordExists = password ? 'Yes' : 'No';
        await user.see(passwordExists, this.userHasPasswordSelector);
        await user.seeInField(this.userPasswordField, "");
    }

    async editUser(adminUser, adminUserAddress) {
        const { user } = this;
        await user.selectEditFrame();
        await this.editUserInformation(adminUser, adminUserAddress);

        return this;
    }
}
