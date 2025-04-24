import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { MainUserPage } from './MainUserPage'; // Assuming MainUserPage is in a separate file
import { ExtendedInformationPage } from './ExtendedInformationPage'; // Assuming ExtendedInformationPage is in a separate file
import { UserHistoryPage } from './UserHistoryPage'; // Assuming UserHistoryPage is in a separate file
import { UserProductsPage } from './UserProductsPage'; // Assuming UserProductsPage is in a separate file
import { UserPaymentInformationPage } from './UserPaymentInformationPage'; // Assuming UserPaymentInformationPage is in a separate file
import { UserAddressPage } from './UserAddressPage'; // Assuming UserAddressPage is in a separate file
import { AdminUser } from './AdminUser'; // Assuming AdminUser is in a separate file
import { AdminUserAddresses } from './AdminUserAddresses'; // Assuming AdminUserAddresses is in a separate file

export class UserList {
    searchEmailInput = '//input[@name="where[oxuser][oxusername]"]';
    searchForm = '#search';
    firstRowName = '//tr[@id="row.1"]//td[2]//div//a';
    usernameSearchField = "where[oxuser][oxusername]";
    newUserButton = '#btn.new';
    newRemarkButton = '#btn.newremark';
    newAddressButton = '#btn.newaddress';

    async find(field, value) {
        const { user } = this;

        await user.selectListFrame();
        await user.fillField(field, value);
        await user.submitForm(this.searchForm);
        await user.selectListFrame(); // Wait for list section to load

        await user.click(this.firstRowName);
        // Wait for list and edit sections to load
        await user.selectListFrame();
        await user.selectEditFrame();

        return new MainUserPage(user);
    }

    async findByUserName(value) {
        return this.find(this.usernameSearchField, value);
    }

    async createNewUser(adminUser, adminUserAddress) {
        const { user } = this;
        const mainUserPage = new MainUserPage(user);

        await user.selectEditFrame();
        await this.loadForm(this.newUserButton, mainUserPage.userFirstNameField);
        await mainUserPage.editUser(adminUser, adminUserAddress);

        return mainUserPage;
    }

    async openExtendedTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbcluser_extend'));

        await user.selectEditFrame();

        return new ExtendedInformationPage(user);
    }

    async openHistoryTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbcluser_remark'));

        await user.selectEditFrame();

        return new UserHistoryPage(user);
    }

    async openProductsTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbcluser_article'));

        await user.selectEditFrame();

        return new UserProductsPage(user);
    }

    async openPaymentTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbcluser_payment'));

        await user.selectEditFrame();

        return new UserPaymentInformationPage(user);
    }

    async openAddressesTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbcluser_address'));

        await user.selectEditFrame();

        return new UserAddressPage(user);
    }

    async createNewRemark(text) {
        const { user } = this;

        await user.click(this.newRemarkButton);

        await user.selectEditFrame();

        await user.waitForPageLoad();

        const historyPage = new UserHistoryPage(user);
        await user.fillField(historyPage.remarkField, text);
        await user.click(await Translator.translate('GENERAL_SAVE'));

        await user.selectEditFrame();

        return historyPage;
    }

    async createNewAddress(adminUserAddresses) {
        const { user } = this;
        const addressPage = new UserAddressPage(user);

        await user.selectEditFrame();
        await this.loadForm(this.newAddressButton, addressPage.addressFirstNameField);
        await addressPage.editUserAddress(adminUserAddresses);

        return addressPage;
    }

    // Assuming loadForm is a method that handles loading a form
    async loadForm(buttonSelector, firstFieldSelector) {
        const { user } = this;
        await user.click(buttonSelector);
        await user.waitForElement(firstFieldSelector);
    }
}
