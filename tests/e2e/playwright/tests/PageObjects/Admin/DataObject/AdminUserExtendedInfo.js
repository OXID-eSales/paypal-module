class AdminUserExtendedInfo {
    eveningPhone = '';
    cellularPhone = '';
    receivesNewsletter = false;
    emailInvalid = false;
    creditRating = '';
    url = '';

    getEveningPhone() {
        return this.eveningPhone;
    }

    setEveningPhone(eveningPhone) {
        this.eveningPhone = eveningPhone;
    }

    getCellularPhone() {
        return this.cellularPhone;
    }

    setCellularPhone(cellularPhone) {
        this.cellularPhone = cellularPhone;
    }

    getReceivesNewsletter() {
        return this.receivesNewsletter;
    }

    setReceivesNewsletter(receivesNewsletter) {
        this.receivesNewsletter = receivesNewsletter;
    }

    getEmailInvalid() {
        return this.emailInvalid;
    }

    setEmailInvalid(emailInvalid) {
        this.emailInvalid = emailInvalid;
    }

    getCreditRating() {
        return this.creditRating;
    }

    setCreditRating(creditRating) {
        this.creditRating = creditRating;
    }

    getUrl() {
        return this.url;
    }

    setUrl(url) {
        this.url = url;
    }
}
