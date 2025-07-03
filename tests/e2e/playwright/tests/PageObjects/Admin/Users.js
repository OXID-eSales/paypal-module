import { Page } from './Page'; // Assuming Page is in a separate file
import { UserList } from './UserList'; // Assuming UserList is in a separate file

export class Users extends Page {
    // Using UserList functionality
    constructor(user) {
        super(user);
        Object.assign(this, UserList);
    }
}
