module.exports = {

    moduleproduction: {
        options: {
            seperator: ";"
        },
        files: {
            "../out/src/js/paypal-admin.min.js": [
                "build/js/paypal-admin.js",
                "node_modules/jquery/dist/jquery.js",
                "node_modules/popper.js/dist/umd/popper.js",
                "node_modules/bootstrap/dist/js/bootstrap.js"
            ]
        }
    }
};