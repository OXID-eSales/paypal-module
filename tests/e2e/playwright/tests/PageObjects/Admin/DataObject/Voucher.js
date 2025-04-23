export class Voucher {
    voucherNr = '';
    voucherQuantity = '';

    async getVoucherNr() {
        return this.voucherNr;
    }

    async setVoucherNr(voucherNr) {
        this.voucherNr = voucherNr;
    }

    async getVoucherQuantity() {
        return this.voucherQuantity;
    }

    async setVoucherQuantity(voucherQuantity) {
        this.voucherQuantity = voucherQuantity;
    }
}
