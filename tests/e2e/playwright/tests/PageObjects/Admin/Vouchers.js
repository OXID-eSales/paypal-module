import { Page } from './Page'; // Assuming Page is in a separate file
import { VoucherList } from './VoucherList'; // Assuming VoucherList is in a separate file

export class Vouchers extends Page {
    // Using VoucherList functionality
    constructor(user) {
        super(user);
        Object.assign(this, VoucherList);
    }
}
