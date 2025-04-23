import { Page } from './Page'; // Assuming Page is in a separate file
import { ManufacturerList } from './ManufacturerList'; // Assuming ManufacturerList is in a separate file

export class Manufacturers extends Page {
    // Using ManufacturerList functionality
    constructor(user) {
        super(user);
        Object.assign(this, ManufacturerList);
    }
}
