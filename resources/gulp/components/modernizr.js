var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');

gulp.task('build-modernizr', [], function(){
    return utils.buildJsGroup([
        config.paths.vendors + 'modernizr/modernizr.js'
    ], 'modernizr', 'vendors/modernizr');
});