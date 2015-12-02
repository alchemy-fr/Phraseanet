var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-jquery-mobile-css', function(){
    return utils.buildCssGroup([
        config.paths.vendors + 'jquery-mobile-bower/css/jquery.mobile-1.3.2.css'
    ], 'jquery-mobile', 'vendors/jquery-mobile');
});

gulp.task('build-jquery-mobile-js', function(){
    return utils.buildJsGroup([
        config.paths.vendors + 'jquery-mobile-bower/js/jquery.mobile-1.3.2.js'
    ], 'jquery-mobile', 'vendors/jquery-mobile');
});

gulp.task('build-jquery-mobile', ['build-jquery-mobile-js', 'build-jquery-mobile-css'], function(){
    // copy jquery mobile assets
    return gulp.src(config.paths.vendors + 'jquery-mobile-bower/css/images/**/*')
        .pipe(gulp.dest( config.paths.build + 'vendors/jquery-mobile/images'));
});