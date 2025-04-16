// playwright/pages/OrderCheckout.js

// A simple translator stub. Replace it with your own translation implementation.
const translate = (key) => key;

class OrderCheckout {
    constructor(page) {
        this.page = page;

        this.URL = '/index.php?cl=order&lang=1';
        this.breadCrumb = '//div[@class="step step-3 active"]';

        this.basketItemAmount = (position) => `//div[@id="list_cartItem_${position}"]/div[2]/div/div`;
        this.basketItemId = (position) => `//div[@id="list_cartItem_${position}"]//ul[contains(@class,"serial-no")]`;
        this.basketItemTitle = (position) => `//div[@id="list_cartItem_${position}"]/div[2]/div/div`;
        this.couponInformation = '//div[contains(@class,"list-group-item")]';

        this.billingAddress = '//div[@id="orderAddress"]/div[@class="card-body"][1]/div';
        this.deliveryAddress = '//div[@id="orderAddress"]/div[@class="card-body"][2]/div';
        this.downloadableProductsAgreement = '#oxdownloadableproductsagreement';
        this.basketItemLabel = (item) => `#list_cartItem_${item} .basket-item-desc .persparamBox`;

        this.editBillingAddress = '//div[@id="orderAddress"]/h2/form[1]/button';
        this.editCart = '//div[@id="orderEditCart"]//h4/button';
        this.editPayment = '//form[@id="orderPayment"]/button';
        this.editShippingMethod = '//form[@id="orderShipping"]/button';
        this.paymentMethod = '//div[contains(@class,"card")]/div[@class="card-body"][2]';
        this.shippingMethod = '//div[contains(@class,"card")]/div[@class="card-body"][1]';
        this.previousStepLink = '';

        this.submitOrderSelector = '//button[contains(@class,"btn-highlight")]';
        this.userRemark = (text) => `//h2[contains(text(),"${text}")]/following-sibling::div`;
        this.userRemarkHeader = 'h2';
        this.basketItemTotalPrice = (position) =>
        `//div[@id="list_cartItem_${position}"]//ul[contains(@class,"unit-price")]`;
    }

    async submitOrder() {
        // Wait for the submit order text to appear
        await this.page.waitForSelector(`text=${translate('SUBMIT_ORDER')}`);
        // Retry clicking could be implemented here if needed.
        await this.page.click(`text=${translate('SUBMIT_ORDER')}`);
        // Wait for page navigation or some element that indicates a page load.
        await this.page.waitForLoadState('networkidle');
        return this;
    }

    async submitOrderSuccessfully() {
        await this.page.waitForSelector(this.submitOrderSelector, { state: 'attached' });
        await this.page.click(this.submitOrderSelector);
        // Assuming ThankYou is defined elsewhere and takes the page object.
        const thankYouPage = new ThankYou(this.page);
        await this.page.waitForSelector(thankYouPage.thankYouPage);
        return thankYouPage;
    }

    async confirmDownloadableProductsAgreement() {
        // Checking the checkbox and verifying is checked
        await this.page.check(this.downloadableProductsAgreement);
        // You might want to implement additional logic if needed.
        return this;
    }

    async goToPreviousStep() {
        await this.page.click(this.previousStepLink);
        // Assuming PaymentCheckout is defined elsewhere
        const paymentPage = new PaymentCheckout(this.page);
        await this.page.waitForSelector(paymentPage.breadCrumb);
        return paymentPage;
    }

    async editUserAddress() {
        await this.page.click(this.editBillingAddress);
        // Assuming UserCheckout is defined elsewhere.
        const userPage = new UserCheckout(this.page);
        await this.page.waitForSelector(userPage.breadCrumb);
        return userPage;
    }

    async editPaymentMethod() {
        await this.page.click(this.editPayment);
        // Assuming PaymentCheckout is defined elsewhere.
        const paymentPage = new PaymentCheckout(this.page);
        await this.page.waitForSelector(paymentPage.breadCrumb);
        return paymentPage;
    }

    async validatePaymentMethod(paymentMethodText) {
        const locator = this.page.locator(this.paymentMethod);
        await expect(locator).toContainText(paymentMethodText);
        return this;
    }

    async editShippingMethod() {
        await this.page.click(this.editShippingMethod);
        const paymentPage = new PaymentCheckout(this.page);
        await this.page.waitForSelector(paymentPage.breadCrumb);
        return paymentPage;
    }

    async validateShippingMethod(shippingMethodText) {
        const locator = this.page.locator(this.shippingMethod);
        await expect(locator).toContainText(shippingMethodText);
        return this;
    }

    async validateCoupon(couponId, couponDiscount) {
        const informationText = `${translate('COUPON')} (${couponId}) ${couponDiscount}`;
        const locator = this.page.locator(this.couponInformation);
        await expect(locator).toContainText(informationText);
        return this;
    }

    async editCart() {
        await this.page.click(this.editCart);
        // Assuming Basket is defined elsewhere.
        const basket = new Basket(this.page);
        await this.page.waitForSelector(basket.breadCrumb);
        return basket;
    }

    /**
     * basketProducts should be an array of objects:
     * [{ id, title, amount, totalPrice }, ...]
     */
    async validateOrderItems(basketProducts) {
        let position = 1;
        for (const product of basketProducts) {
            await expect(this.page.locator(this.basketItemId(position))).toContainText(
                `${translate('PRODUCT_NO')} ${product.id}`
            );
            await expect(this.page.locator(this.basketItemTitle(position))).toContainText(product.title);
            await expect(this.page.locator(this.basketItemTotalPrice(position))).toContainText(
                String(product.totalPrice)
            );
            await expect(this.page.locator(this.basketItemAmount(position))).toContainText(
                String(product.amount)
            );
            position++;
        }
        return this;
    }

    async seeOrderItemLabel(label, item) {
        const locator = this.page.locator(this.basketItemLabel(item));
        await expect(locator).toContainText(`${translate('LABEL')} ${label}`);
        return this;
    }

    async dontSeeOrderItemHasLabel(item) {
        const locator = this.page.locator(this.basketItemLabel(item));
        await expect(locator).toHaveCount(0);
        return this;
    }

    async validateUserBillingAddress(userBillAddress) {
        const addressInfo = this._convertBillInformationIntoString(userBillAddress);
        const billingText = await this.page.textContent(this.billingAddress);
        // Assuming a helper that clears or normalizes strings.
        if (this._clearString(addressInfo) !== this._clearString(billingText)) {
            throw new Error('Billing address validation failed');
        }
        return this;
    }

    async validateUserDeliveryAddress(userDelAddress) {
        const addressInfo = this._convertDeliveryAddressIntoString(userDelAddress);
        const deliveryText = await this.page.textContent(this.deliveryAddress);
        if (this._clearString(addressInfo) !== this._clearString(deliveryText)) {
            throw new Error('Delivery address validation failed');
        }
        return this;
    }

    async seeUserDeliveryAddressPart(addressPart) {
        const locator = this.page.locator(this.deliveryAddress);
        await expect(locator).toContainText(addressPart);
        return this;
    }

    async validateRemarkText(userRemarkText) {
        // Check header and the remark text
        await expect(this.page.locator(this.userRemarkHeader)).toContainText(translate('WHAT_I_WANTED_TO_SAY'));
        await expect(this.page.locator(this.userRemark(translate('WHAT_I_WANTED_TO_SAY')))).toContainText(userRemarkText);
        return this;
    }

    // ---------- Private helper methods ----------
    _convertBillInformationIntoString(userAddress) {
        let transformedAddress = this._convertAddressArrayIntoString(userAddress);
        transformedAddress += `${translate('EMAIL')} `;
        transformedAddress += this._getAddressElement(userAddress, 'userLoginNameField');
        transformedAddress += `${translate('PHONE')} `;
        transformedAddress += this._getAddressElement(userAddress, 'fonNr');
        transformedAddress += ` | ${translate('FAX')} `;
        transformedAddress += this._getAddressElement(userAddress, 'faxNr');
        transformedAddress += `${translate('CELLUAR_PHONE')} `;
        transformedAddress += this._getAddressElement(userAddress, 'userMobFonField');
        transformedAddress += `${translate('PERSONAL_PHONE')} `;
        transformedAddress += this._getAddressElement(userAddress, 'userPrivateFonField');
        return transformedAddress;
    }

    _convertDeliveryAddressIntoString(userAddress) {
        let transformedAddress = this._convertAddressArrayIntoString(userAddress);
        transformedAddress += `${translate('PHONE')} `;
        transformedAddress += this._getAddressElement(userAddress, 'fonNr');
        transformedAddress += `${translate('FAX')} `;
        transformedAddress += this._getAddressElement(userAddress, 'faxNr');
        return transformedAddress;
    }

    _convertAddressArrayIntoString(userAddress) {
        let transformedAddress = this._getAddressElement(userAddress, 'companyName');
        transformedAddress += this._getAddressElement(userAddress, 'additionalInfo');
        transformedAddress += this._getAddressElement(userAddress, 'userUstIDField', `${translate('VAT_ID_NUMBER')} `);
        transformedAddress += this._getAddressElement(userAddress, 'userSalutation');
        transformedAddress += this._getAddressElement(userAddress, 'userFirstName');
        transformedAddress += this._getAddressElement(userAddress, 'userLastName');
        transformedAddress += this._getAddressElement(userAddress, 'street');
        transformedAddress += this._getAddressElement(userAddress, 'streetNr');
        if (userAddress.stateId) {
            transformedAddress += 'BE ';
        }
        transformedAddress += this._getAddressElement(userAddress, 'ZIP');
        transformedAddress += this._getAddressElement(userAddress, 'city');
        transformedAddress += this._getAddressElement(userAddress, 'countryId');
        return transformedAddress;
    }

    _getAddressElement(address, element, label = '') {
        return address[element] ? `${label}${address[element]} ` : '';
    }

    _clearString(str) {
        // Basic implementation to remove extra spaces or new lines. Adjust as needed.
        return str.replace(/\s+/g, ' ').trim();
    }
}

// Export the class for use in your Playwright tests
module.exports = OrderCheckout;
