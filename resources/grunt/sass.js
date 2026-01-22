const sass = require('sass');

module.exports = {
    moduledevelopment: {
        options: {
            implementation: sass,
            update: true,
            style: 'nested'
        },
        files: {
            "../out/src/css/bootstrap.min.css": "node_modules/bootstrap/scss/bootstrap.scss",
            "../out/src/css/paypal.min.css": "build/scss/paypal.scss",
            "../out/src/css/paypal-admin.min.css": "build/scss/paypal-admin.scss",
        }
    },

    moduleproduction: {
        options: {
            implementation: sass,
            update: true,
            style: 'compressed'
        },
        files: {
            "../out/src/css/bootstrap.css": "node_modules/bootstrap/scss/bootstrap.scss",
            "../out/src/css/paypal.css": "build/scss/paypal.scss",
            "../out/src/css/paypal-admin.css": "build/scss/paypal-admin.scss",
        }
    }
};

