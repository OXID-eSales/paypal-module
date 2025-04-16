import { AdminPanel } from './AdminPanel'; // Assuming AdminPanel is in a separate file
import { Translator } from './Translator'; // Assuming Translator is in a separate file

export class AdminLoginPage extends AdminPanel {
    URL = '/admin/';

    userAccountLoginName = '#usr';
    userAccountLoginPassword = '#pwd';
    userAccountLoginButton = '.btn';

    async login(userName, userPassword) {
        const { user } = this;

        await user.fillField(this.userAccountLoginName, userName);
        await user.fillField(this.userAccountLoginPassword, userPassword);
        await user.click(this.userAccountLoginButton);

        const adminPanel = new AdminPanel(user);
        await user.waitForElement(adminPanel.adminNavigation);
        await user.selectBaseFrame();
        await user.waitForText(await Translator.translate('NAVIGATION_HOME'));
        await user.see(await Translator.translate('HOME_DESC'));

        return adminPanel;
    }

    async seeLoginForm() {
        const { user } = this;

        await user.seeElement(this.userAccountLoginName);
        await user.seeElement(this.userAccountLoginPassword);
        await user.seeElement(this.userAccountLoginButton);

        return this;
    }
}
