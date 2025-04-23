import { Page } from './Page'; // Assuming Page is in a separate file
import { CategoryList } from './CategoryList'; // Assuming CategoryList is in a separate file

export class ProductCategories extends Page {
    // Using CategoryList functionality
    constructor(user) {
        super(user);
        Object.assign(this, CategoryList);
    }
}
