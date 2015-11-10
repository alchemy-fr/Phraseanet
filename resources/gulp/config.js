var gutil = require("gulp-util");
var configPaths = {
    src: 'resources/www/',
    vendors: 'tmp-assets/',
    distVendors: 'www/assets/', //@deprecated
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
