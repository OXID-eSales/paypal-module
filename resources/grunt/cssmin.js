module.exports = {
    options: {
        mergeIntoShorthands: false,
        roundingPrecision: -1
    },
    target: {
        files: [
            {
                expand: true,
                cwd: '../assets/src/css/',
                src: ['*.css', '!*.min.css'],
                dest: '../assets/src/css/',
                ext: '.min.css',
                extDot: 'last'
        }
        ]
    }
};
