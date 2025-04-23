import { Voucher } from './Voucher'; // Assuming Voucher is in a separate file
import { VoucherSerie } from './VoucherSerie'; // Assuming VoucherSerie is in a separate file
import { Page } from './Page'; // Assuming Page is in a separate file

export class MainVoucherPage extends Page {
	titleInput = "//input[@name='editval[oxvoucherseries__oxserienr]']";
	voucherType = "//select[@name='editval[oxvoucherseries__oxdiscounttype]']";
	saveButton = "//input[@name='save']";
	discountField = "//input[@name='editval[oxvoucherseries__oxdiscount]']";
	allowSameSeriesYes = "//input[@name='editval[oxvoucherseries__oxallowsameseries]'][@value='1']";
	allowSameSeriesNo = "//input[@name='editval[oxvoucherseries__oxallowsameseries]'][@value='0']";
	voucherNr = "//input[@name='voucherNr']";
	voucherQuantity = "//input[@name='voucherAmount']";
	generateButton = "//input[@name='save' and @value='Generate']";

	async createVoucherSerie(voucher) {
		const { user } = this;

		await user.fillField(this.titleInput, voucher.getTitle());
		await user.selectOption(this.voucherType, voucher.getVoucherType());
		await user.click(this.saveButton);
		await user.waitForDocumentReadyState();

		return this;
	}

	async createVoucher(voucher) {
		const { user } = this;

		await user.fillField(this.voucherNr, voucher.getVoucherNr());
		await user.fillField(this.voucherQuantity, voucher.getVoucherQuantity());
		await user.click(this.generateButton);
		await user.waitForDocumentReadyState();

		return this;
	}

	async seeVoucherSerie(voucher) {
		const { user } = this;

		await user.seeInField(this.titleInput, voucher.getTitle());
		await user.seeInField(this.voucherType, voucher.getVoucherType());

		return this;
	}

	async seeVoucher(voucher) {
		const { user } = this;

		await user.seeInField(this.voucherNr, voucher.getVoucherNr());
		await user.seeInField(this.voucherQuantity, voucher.getVoucherQuantity());

		return this;
	}

	async checkVoucherDiscountFieldForShipfreeVoucher() {
		const { user } = this;

		await user.selectOption(this.voucherType, 'shipfree');
		await user.waitForElementVisible(this.discountField, 5);

		await user.seeElement(this.discountField);
		await user.seeElement(this.discountField, { disabled: 'true' });
		await user.seeInField(this.discountField, '0');
	}

	async checkAllowSameSeriesRadioDisabled() {
		const { user } = this;

		await user.waitForElementVisible(this.allowSameSeriesYes, 5);

		await user.seeCheckboxIsChecked(this.allowSameSeriesNo);
		await user.seeElement(this.allowSameSeriesYes, { disabled: 'true' });
		await user.dontSeeElement(this.allowSameSeriesNo, { disabled: 'true' });

		return this;
	}
}
