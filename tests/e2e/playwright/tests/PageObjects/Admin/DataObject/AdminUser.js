class AdminUser {
    active = false;
    username = '';
    customerNumber = '';
    ustid = '';
    birthday = '';
    birthMonth = '';
    birthYear = '';
    password = '';
    userRights = '';

    getActive() {
        return this.active;
    }

    setActive(active) {
        this.active = active;
    }

    getUsername() {
        return this.username;
    }

    setUsername(username) {
        this.username = username;
    }

    getCustomerNumber() {
        return this.customerNumber;
    }

    setCustomerNumber(customerNumber) {
        this.customerNumber = customerNumber;
    }

    getUstid() {
        return this.ustid;
    }

    setUstid(ustid) {
        this.ustid = ustid;
    }

    getBirthday() {
        return this.birthday;
    }

    setBirthday(birthday) {
        this.birthday = birthday;
    }

    getBirthMonth() {
        return this.birthMonth;
    }

    setBirthMonth(birthMonth) {
        this.birthMonth = birthMonth;
    }

    getBirthYear() {
        return this.birthYear;
    }

    setBirthYear(birthYear) {
        this.birthYear = birthYear;
    }

    getPassword() {
        return this.password;
    }

    setPassword(password) {
        this.password = password;
    }

    getUserRights() {
        return this.userRights;
    }

    setUserRights(userRights) {
        this.userRights = userRights;
    }
}
