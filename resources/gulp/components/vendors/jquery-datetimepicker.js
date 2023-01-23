var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-jquery-datetimepicker', function () {
    gulp.start('copy-jquery-datetimepicker-js');
});

gulp.task('copy-jquery-datetimepicker-js', ['copy-jquery-datetimepicker-css'], function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'jquery-datetimepicker/build/jquery.datetimepicker.full.js'
    ], 'jquery-datetimepicker', 'vendors/jquery-datetimepicker');
});

gulp.task('copy-jquery-datetimepicker-css', function(){
    return utils.buildCssGroup([
        config.paths.nodes + 'jquery-datetimepicker/jquery.datetimepicker.css'
    ], 'jquery-datetimepicker', 'vendors/jquery-datetimepicker');
});
