export class VoucherSerie {
    title = '';
    voucherType = '';

    async getTitle() {
        return this.title;
    }

    async setTitle(title) {
        this.title = title;
    }

    async getVoucherType() {
        return this.voucherType;
    }

    async setVoucherType(voucherType) {
        this.voucherType = voucherType;
    }
}
