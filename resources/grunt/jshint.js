module.exports = {

    options: {
        curly: true,
        eqeqeq: false,
        eqnull: true,
        browser: true,
        globals: {
            jQuery: true
        }
    },
    moduleproduction: {
        src: [
            "build/js/*.js"
        ]
    }
};