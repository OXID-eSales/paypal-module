import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { Page } from './Page'; // Assuming Page is in a separate file
import { AdminUserExtendedInfo } from './AdminUserExtendedInfo'; // Assuming AdminUserExtendedInfo is in a separate file
import { AdminUserAddresses } from './AdminUserAddresses'; // Assuming AdminUserAddresses is in a separate file

export class ExtendedInformationPage extends Page {
    extendedInfoTabUserAddress = "#test_userAddress";
    extendedInfoEveningPhoneField = "//input[@name='editval[oxuser__oxprivfon]']";
    extendedInfoCellularPhoneField = "//input[@name='editval[oxuser__oxmobfon]']";
    extendedInfoReceivesNewsletterField = "/descendant::input[@name='editnews'][2]";
    extendedInfoEmailInvalidField = "/descendant::input[@name='emailfailed'][2]";
    extendedInfoCreditRatingField = "//input[@name='editval[oxuser__oxboni]']";
    extendedInfoUrlField = "//input[@name='editval[oxuser__oxurl]']";

    async editExtendedInfo(adminUserExtendedInfo) {
        const { user } = this;

        await user.fillField(this.extendedInfoEveningPhoneField, adminUserExtendedInfo.getEveningPhone());
        await user.fillField(this.extendedInfoCellularPhoneField, adminUserExtendedInfo.getCellularPhone());

        if (adminUserExtendedInfo.getReceivesNewsletter()) {
            await user.checkOption(this.extendedInfoReceivesNewsletterField);
        } else {
            await user.uncheckOption(this.extendedInfoReceivesNewsletterField);
        }

        if (adminUserExtendedInfo.getEmailInvalid()) {
            await user.checkOption(this.extendedInfoEmailInvalidField);
        } else {
            await user.uncheckOption(this.extendedInfoEmailInvalidField);
        }

        await user.fillField(this.extendedInfoCreditRatingField, adminUserExtendedInfo.getCreditRating());
        await user.fillField(this.extendedInfoUrlField, adminUserExtendedInfo.getUrl());
        await user.click(await Translator.translate('GENERAL_SAVE'));
        await user.waitForDocumentReadyState();

        return this;
    }

    async seeUserAddress(adminUserAddress) {
        const { user } = this;
        const addressInformation = `${adminUserAddress.getTitle()} ${adminUserAddress.getFirstName()} ${adminUserAddress.getLastName()} 
        ${adminUserAddress.getCompany()} ${adminUserAddress.getStreet()} ${adminUserAddress.getStreetNumber()} 
        ${adminUserAddress.getStateId()} ${adminUserAddress.getZip()} ${adminUserAddress.getCity()} 
        ${adminUserAddress.getAdditionalInfo()} ${adminUserAddress.getCountryId()} ${adminUserAddress.getPhone()}`;

        await user.see(addressInformation, this.extendedInfoTabUserAddress);
        return this;
    }

    async seeUserExtendedInformation(adminUserExtendedInfo) {
        const { user } = this;

        await user.seeInField(this.extendedInfoEveningPhoneField, adminUserExtendedInfo.getEveningPhone());
        await user.seeInField(this.extendedInfoCellularPhoneField, adminUserExtendedInfo.getCellularPhone());

        if (adminUserExtendedInfo.getEmailInvalid()) {
            await user.seeCheckboxIsChecked(this.extendedInfoEmailInvalidField);
        } else {
            await user.dontSeeCheckboxIsChecked(this.extendedInfoEmailInvalidField);
        }

        if (adminUserExtendedInfo.getReceivesNewsletter()) {
            await user.seeCheckboxIsChecked(this.extendedInfoReceivesNewsletterField);
        } else {
            await user.dontSeeCheckboxIsChecked(this.extendedInfoReceivesNewsletterField);
        }

        await user.seeInField(this.extendedInfoCreditRatingField, adminUserExtendedInfo.getCreditRating());
        await user.seeInField(this.extendedInfoUrlField, adminUserExtendedInfo.getUrl());

        return this;
    }
}
