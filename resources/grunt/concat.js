module.exports = {

    moduleproduction: {
        options: {
            seperator: ";"
        },
        files: {
            "../assets/src/js/paypal-admin.min.js": [
                "build/js/paypal-admin.js"
            ],
            "../assets/src/js/bootstrap.min.js": [
                "../node_modules/popper.js/dist/umd/popper.js",
                "../node_modules/bootstrap/dist/js/bootstrap.js"
            ],
            "../assets/src/js/paypal-frontend.min.js": [
                "build/js/paypal-frontend-paypal.js",
                "build/js/paypal-frontend-googlepay-3ds.js",
                "build/js/paypal-frontend-hateoaslinks.js",
                "build/js/paypal-frontend-paypal-vault-checkout.js",
                "build/js/paypal-frontend-payment-controller-base.js",
                "build/js/paypal-frontend-standard-payment-controller.js",
                "build/js/paypal-frontend-acdc-payment-controller.js",
                "build/js/paypal-frontend-googlepay-payment-controller.js",
                "build/js/paypal-frontend-applepay-payment-controller.js",
                "build/js/paypal-frontend-express-payment-controller.js",
                "build/js/paypal-frontend-variant-observer.js"
            ]
        }
    }
};