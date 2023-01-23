var path = require("path");
var fs = require("fs");
var gutil = require("gulp-util");
var configPaths = {
    src: 'resources/www/',
    vendors: 'www/bower_components/',
    build: 'www/assets/',
    dist: 'www/',
    nodes: 'node_modules/'
};

exports.paths = configPaths;

/**
 * ensure external override config is accessible
 * @returns {boolean}
 */
exports.checkPath = function(userPath, log) {
    "use strict";
    try {
        fs.statSync(path.resolve(userPath) );
        if( log === true ) {
            gutil.log(gutil.colors.green('[INFO]'), 'folder "'+userPath+'" exists');
        }
        return true;
    } catch(e) {
        if( log === true ) {
            gutil.log(gutil.colors.red('[WARNING]'), 'folder "' + userPath + '" not found');
        }
        return false;
    }
};
exports.errorHandler = function(title) {
    'use strict';

    return function(err) {
        gutil.log(gutil.colors.red('[' + title + ']'), err.toString());
        this.emit('end');
    };
};
