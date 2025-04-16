import { Page } from './Page'; // Assuming Page is in a separate file
import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { SystemTab } from './CoreSetting/SystemTab'; // Assuming SystemTab is in a separate file
import { SettingsTab } from './CoreSetting/SettingsTab'; // Assuming SettingsTab is in a separate file
import { LicenseTab } from './CoreSetting/LicenseTab'; // Assuming LicenseTab is in a separate file
import { PerformanceTab } from './CoreSetting/PerformanceTab'; // Assuming PerformanceTab is in a separate file
import { SEOTab } from './CoreSetting/SEOTab'; // Assuming SEOTab is in a separate file
import { CachingTab } from './CoreSetting/CachingTab'; // Assuming CachingTab is in a separate file

export class CoreSettings extends Page {
    newShopButton = '#btn.new';
    newShopNameField = '#shopname';
    shopParentSelect = '#shopparent';
    activeShopSelect = 'editval[oxshops__oxactive]';
    masterShopInSelectOption = '#shopparent option:nth-child(2)';
    inheritParentProductsOption = 'editval[oxshops__oxisinherited]';
    shopName = 'editval[oxshops__oxname]';
    tabPerformance = 'tbclshop_performance';
    tabSEO = 'tbclshop_seo';
    tabCaching = 'tbclshop_cache';
    tabLicense = 'tbclshop_license';

    async createNewShop(shopName) {
        const { user } = this;

        await user.selectEditFrame();
        await user.click(this.newShopButton);
        await user.wait(3);

        // Create new shop
        await user.fillField(this.newShopNameField, shopName);
        await user.checkOption(this.inheritParentProductsOption);
        const option = await user.grabTextFrom(this.masterShopInSelectOption);
        await user.selectOption(this.shopParentSelect, option);
        await user.click(await Translator.translate('GENERAL_SAVE'));
        await user.wait(5);
        await user.checkOption(this.activeShopSelect);
        await user.click(await Translator.translate('GENERAL_SAVE'));

        await user.selectListFrame();
        await user.waitForText(shopName, 10);

        return this;
    }

    async selectShopInList(subShopName) {
        const { user } = this;

        await user.selectListFrame();
        await user.waitForText(subShopName);
        await user.click(subShopName);
        await user.selectEditFrame();
        await user.waitForPageLoad();
        await user.seeInField(this.shopName, subShopName);

        return this;
    }

    async openSystemTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclshop_system'));

        await user.selectListFrame();
        await user.selectEditFrame();

        return new SystemTab(user);
    }

    async openSettingsTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate('tbclshop_config'));

        await user.selectListFrame();
        await user.selectEditFrame();

        return new SettingsTab(user);
    }

    async openLicenseTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate(this.tabLicense));

        await user.selectListFrame();
        await user.selectEditFrame();
        await user.waitForText(await Translator.translate('SHOP_LICENSE_VERSION'));
        await user.waitForPageLoad();

        return new LicenseTab(user);
    }

    async openPerformanceTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate(this.tabPerformance));

        await user.selectListFrame();
        await user.selectEditFrame();

        return new PerformanceTab(user);
    }

    async openSEOTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate(this.tabSEO));

        await user.selectListFrame();
        await user.selectEditFrame();

        return new SEOTab(user);
    }

    async openCacheTab() {
        const { user } = this;

        await user.selectListFrame();
        await user.click(await Translator.translate(this.tabCaching));

        await user.selectListFrame();
        await user.selectEditFrame();

        return new CachingTab(user);
    }
}
