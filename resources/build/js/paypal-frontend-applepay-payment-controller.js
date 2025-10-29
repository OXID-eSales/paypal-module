(function () {
    let ApplePayPaymentController = function (config) {
        // Inherit from base controller`
        PayPalPaymentControllerBase.call(this, config);

        this.resetCurrentOrder();

        return this;
    };

    window.addEventListener('load', function() {
        if (
            'undefined' == typeof window.PayPalPayment &&
            'undefined' !== typeof window.ApplePayPayPalPaymentControllerConfiguratorDefaults
        ) {
            window.PayPalPayment = new ApplePayPaymentController();
        }
    });
})();
