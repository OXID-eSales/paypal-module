import { Page } from './Page'; // Assuming Page is in a separate file
import { Translator } from './Translator'; // Assuming Translator is in a separate file

export class CountryList extends Page {
    searchForm = '#search';
    titleSearchField = "where[oxcountry][oxtitle]";

    async selectCountry(country) {
        const { user } = this;

        await user.selectListFrame();
        await user.fillField(this.titleSearchField, country);
        await user.submitForm(this.searchForm);

        await user.selectListFrame();
        await user.click(country);
        await user.selectEditFrame();

        return this;
    }
}
