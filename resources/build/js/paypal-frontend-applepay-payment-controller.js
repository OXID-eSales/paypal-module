(function () {
    let ApplePayPaymentController = function (config) {
        // Inherit from base controller`
        PayPalPaymentControllerBase.call(this, config);

        return this.init();
    };

    window.addEventListener('load', function() {
        window.PayPalPayment = new ApplePayPaymentController();

    });
})();
