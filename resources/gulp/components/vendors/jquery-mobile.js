var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-jquery-mobile-css', function(){
    return utils.buildCssGroup([
        config.paths.nodes + 'jquery-mobile/css/themes/default/jquery.mobile.css'
    ], 'jquery-mobile', 'vendors/jquery-mobile');
});

gulp.task('build-jquery-mobile-js', function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'jquery-mobile/js/jquery.mobile.js'
    ], 'jquery-mobile', 'vendors/jquery-mobile');
});

gulp.task('build-jquery-mobile', ['build-jquery-mobile-js', 'build-jquery-mobile-css'], function(){
    // copy jquery mobile assets
    return gulp.src(config.paths.nodes + 'jquery-mobile-bower/css/themes/default/images/**/*')
        .pipe(gulp.dest( config.paths.build + 'vendors/jquery-mobile/images'));
});