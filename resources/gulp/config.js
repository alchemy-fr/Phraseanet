var gutil = require("gulp-util");
var configPaths = {
    vendors: 'tmp-assets/',
    dist: 'www/assets/'
};

exports.paths = configPaths;

exports.errorHandler = function(title) {
    'use strict';

    return function(err) {
        gutil.log(gutil.colors.red('[' + title + ']'), err.toString());
        this.emit('end');
    };
};
