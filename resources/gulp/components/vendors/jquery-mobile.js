var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-jquery-mobile-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'vendors/jquery-mobile/jquery.mobile-1.4.5.min.css'
    ], 'jquery-mobile', 'vendors/jquery-mobile');
});

gulp.task('build-jquery-mobile-js', function(){
    return utils.buildJsGroup([
        config.paths.src + 'vendors/jquery-mobile/jquery.mobile-1.4.5.min.js'
    ], 'jquery-mobile', 'vendors/jquery-mobile');
});

gulp.task('build-jquery-mobile', ['build-jquery-mobile-js', 'build-jquery-mobile-css'], function(){
    // copy jquery mobile assets
    return gulp.src(config.paths.src + 'vendors/jquery-mobile/images/**/*')
        .pipe(gulp.dest( config.paths.build + 'vendors/jquery-mobile/images'));
});