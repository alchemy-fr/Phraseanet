var gutil = require("gulp-util");
var configPaths = {
    src: 'resources/www/',
    vendors: 'www/bower_components/',
    build: 'www/assets/',
    dist: 'www/'
};

exports.paths = configPaths;

exports.errorHandler = function(title) {
    'use strict';

    return function(err) {
        gutil.log(gutil.colors.red('[' + title + ']'), err.toString());
        this.emit('end');
    };
};
