import { Translator } from './Translator'; // Assuming Translator is in a separate file
import { Page } from './Page'; // Assuming Page is in a separate file

export class OrderOverviewPage extends Page {
    orderProductLabel = '.box table tbody tr:nth-of-type(%d) td:nth-of-type(6)';
    shipForm = '#sendorder';
    orderSendMailCheckbox = 'input[name=sendmail]';
    todayOrdersCount = "//tr[td[contains(text(),'%s')]]/td[2]/b";
    todayOrdersSum = "//tr[td[contains(text(),'%s')]]/td[2]";
    totalOrdersCount = "//tr[td[contains(text(),'%s')]]/td[2]/b";
    totalOrdersSum = "//tr[td[contains(text(),'%s')]]/td[2]";

    async seeOrderProductLabel(label, product) {
        const { user } = this;
        await user.see(
            `${await Translator.translate('GENERAL_LABEL')}: ${label}`,
            this.orderProductLabel.replace('%d', product)
        );
        return this;
    }

    async dontSeeOrderProductHasLabel(product) {
        const { user } = this;
        await user.dontSeeElement(this.orderProductLabel.replace('%d', product));
        return this;
    }

    async shipOrderWithEmail() {
        const { user } = this;

        await user.checkOption(this.orderSendMailCheckbox);
        await user.submitForm(this.shipForm);

        await user.see(await Translator.translate('GENERAL_SENDON'));
        return this;
    }

    async seeOrdersTodayCount(count) {
        const { user } = this;
        await user.see(count, this.todayOrdersCount.replace('%s', await Translator.translate('ORDER_OVERVIEW_ORDERAMTODAY')));
        return this;
    }

    async seeOrdersTodaySum(sum) {
        const { user } = this;
        await user.see(sum, this.todayOrdersSum.replace('%s', await Translator.translate('ORDER_OVERVIEW_ORDERSUMTODAY')));
        return this;
    }

    async seeTotalOrdersCount(count) {
        const { user } = this;
        await user.see(count, this.totalOrdersCount.replace('%s', await Translator.translate('ORDER_OVERVIEW_ORDERAMTOTAL')));
        return this;
    }

    async seeTotalOrdersSum(sum) {
        const { user } = this;
        await user.see(sum, this.totalOrdersSum.replace('%s', await Translator.translate('ORDER_OVERVIEW_ORDERSUMTOTAL')));
        return this;
    }
}
