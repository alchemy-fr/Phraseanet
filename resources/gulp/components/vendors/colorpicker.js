var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('copy-colorpicker-images', function(){
    return gulp.src([config.paths.src + 'vendors/colorpicker/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'vendors/colorpicker/images'));
});

gulp.task('build-colorpicker-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'vendors/colorpicker/styles/colorpicker.scss'
    ], 'colorpicker', 'vendors/colorpicker');
});

gulp.task('build-colorpicker', ['build-colorpicker-css', 'copy-colorpicker-images'], function(){
    return utils.buildJsGroup([
        config.paths.src + 'vendors/colorpicker/js/colorpicker.js'
    ], 'colorpicker', 'vendors/colorpicker');
});