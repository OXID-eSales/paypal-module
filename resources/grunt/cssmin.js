module.exports = {
    options: {
        mergeIntoShorthands: false,
        roundingPrecision: -1
    },
    target: {
        files: [
            {
                expand: true,
                cwd: '../out/src/css',
                src: ['*.css', '!*.min.css'],
                dest: '../out/src/css',
                ext: '.min.css',
                extDot: 'last'
        }
        ]
    }
};
