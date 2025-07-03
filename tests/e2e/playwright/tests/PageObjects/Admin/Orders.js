import { Page } from './Page'; // Assuming Page is in a separate file
import { OrderList } from './OrderList'; // Assuming OrderList is in a separate file

export class Orders extends Page {
    // Using OrderList functionality
    constructor(user) {
        super(user);
        Object.assign(this, OrderList);
    }
}
