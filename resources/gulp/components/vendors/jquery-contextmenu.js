var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('copy-jquery-contextmenu-images', function(){
    return gulp.src([config.paths.src + 'vendors/jquery-contextmenu/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'vendors/jquery-contextmenu/images'));
});

gulp.task('build-jquery-contextmenu-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'vendors/jquery-contextmenu/styles/jquery.contextmenu.scss'
    ], 'jquery-contextmenu', 'vendors/jquery-contextmenu');
});

gulp.task('build-jquery-contextmenu', ['build-jquery-contextmenu-css', 'copy-jquery-contextmenu-images'], function(){

});