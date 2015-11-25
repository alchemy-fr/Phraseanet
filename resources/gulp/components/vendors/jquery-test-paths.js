var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-jquery-test-paths', function(){
    return utils.buildJsGroup([
        config.paths.src + 'vendors/jquery-test-paths/jquery.test-paths.js'
    ], 'jquery.test-paths', 'vendors/jquery-test-paths');
});