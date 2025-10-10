const sass = require('sass');

module.exports = {
    moduledevelopment: {
        options: {
            implementation: sass,
            update: true,
            style: 'nested'
        },
        files: {
            "../assets/src/css/bootstrap.min.css": "node_modules/bootstrap/scss/bootstrap.scss",
            "../assets/src/css/paypal.min.css": "build/scss/paypal.scss",
            "../assets/src/css/paypal-admin.min.css": "build/scss/paypal-admin.scss",
        }
    },

    moduleproduction: {
        options: {
            implementation: sass,
            update: true,
            style: 'compressed'
        },
        files: {
            "../assets/src/css/bootstrap.css": "node_modules/bootstrap/scss/bootstrap.scss",
            "../assets/src/css/paypal.css": "build/scss/paypal.scss",
            "../assets/src/css/paypal-admin.css": "build/scss/paypal-admin.scss",
        }
    }
};

