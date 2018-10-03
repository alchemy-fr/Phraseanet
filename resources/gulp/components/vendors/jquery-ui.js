var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('copy-jquery-ui-images', function(){
    return gulp.src([config.paths.src + 'vendors/jquery-ui/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'vendors/jquery-ui/images'));
});

// DEPRECATED > theme is loaded in skin 000000
gulp.task('copy-jquery-ui-theme1', function(){
    utils.buildCssGroup([config.paths.src + 'vendors/jquery-ui/dark-hive.scss'], 'dark-hive', 'vendors/jquery-ui/css/');
});
// DEPRECATED > theme is loaded in skin 959595
gulp.task('copy-jquery-ui-theme2', function(){
    utils.buildCssGroup([config.paths.src + 'vendors/jquery-ui/ui-lightness.scss'], 'ui-lightness', 'vendors/jquery-ui/css/');
});

gulp.task('build-jquery-ui', ['copy-jquery-ui-images', 'copy-jquery-ui-theme1', 'copy-jquery-ui-theme2'], function(){
    // copy jquery ui assets
    return utils.buildJsGroup([
        config.paths.nodes + 'jquery-ui-dist/jquery-ui.js'
    ], 'jquery-ui', 'vendors/jquery-ui');
});