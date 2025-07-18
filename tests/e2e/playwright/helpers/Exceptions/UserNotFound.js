export class UserNotFound extends Error {
    constructor(message = 'User is not found') {
        super(message);
        this.name = 'UserNotFound';
    }
}
