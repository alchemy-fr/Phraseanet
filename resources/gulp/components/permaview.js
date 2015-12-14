var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');
var debugMode = false;

gulp.task('copy-permaview-images', function(){
    return gulp.src([config.paths.src + 'permaview/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'permaview/images'));
});
gulp.task('build-permaview-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'permaview/styles/main.scss'
    ], 'permaview', 'permaview/css/', debugMode);
});

gulp.task('build-permaview-js', function(){
    // nothing to build
    /*
    var permaviewGroup =  [
        config.paths.src + 'vendors/jquery-mousewheel/js/jquery.mousewheel.js',
        config.paths.src + 'vendors/jquery-image-enhancer/js/jquery.image_enhancer.js'
    ];
    return utils.buildJsGroup(permaviewGroup, 'permaview', 'permaview/js', debugMode);
    */
});

gulp.task('watch-permaview-js', function() {
    debugMode = true;
    return gulp.watch(config.paths.src + 'permaview/**/*.js', ['build-permaview-js']);
});

gulp.task('watch-permaview-css', function() {
    debugMode = true;
    gulp.watch(config.paths.src + 'permaview/**/*.scss', ['build-permaview-css']);
});

gulp.task('build-permaview', ['copy-permaview-images', 'build-permaview-css'], function(){
    return gulp.start('build-permaview-js');
});