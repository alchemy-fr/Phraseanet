var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');

gulp.task('build-requirejs', function(){
    return utils.buildJsGroup([
        config.paths.vendors + 'requirejs/require.js'
    ], 'require', 'vendors/requirejs');
});