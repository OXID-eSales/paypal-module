module.exports = {

    options: {
        processors: [
            require('autoprefixer')({browserlist: ['last 2 versions', 'ie 11']})
        ]
    },
    dist: {
        files: [
            {
                expand: true,
                cwd: '../assets/src/css',
                src: ['*.css', '!*.min.css'],
                dest: '../assets/src/css',
                ext: '.css',
                extDot: 'last'
            }
        ]
    }
};
