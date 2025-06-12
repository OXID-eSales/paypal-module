module.exports = {
    options: {
        mergeIntoShorthands: false,
        roundingPrecision: -1
    },
    target: {
        files: [
            {
                expand: true,
                cwd: '../src/assets/css',
                src: ['*.css', '!*.min.css'],
                dest: '../src/assets/css',
                ext: '.min.css',
                extDot: 'last'
        }
        ]
    }
};
