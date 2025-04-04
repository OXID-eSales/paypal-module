module.exports = {

    options: {
        preserveComments: false
    },
    moduleproduction: {
        files: {
            "../out/src/js/paypal-admin.min.js": [
                "build/js/paypal-admin.js",
                "node_modules/jquery/dist/jquery.js",
                "node_modules/popper.js/dist/umd/popper.js",
                "node_modules/bootstrap/dist/js/bootstrap.js"
            ],
            "../out/src/js/paypal-frontend.min.js": [
                "build/js/paypal-frontend-paypal.js",
                "build/js/paypal-frontend-googlepay.js",
                "build/js/paypal-frontend-googlepay-3ds.js",
                "build/js/paypal-frontend-hateoaslinks.js",
                "build/js/paypal-frontend-paypal-vault-checkout.js",
                "build/js/paypal-frontend-payment-controller.js",
            ]
        }
    }
};