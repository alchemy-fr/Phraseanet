var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');
var debugMode = false;

gulp.task('copy-setup-images', function(){
    return gulp.src([config.paths.src + 'setup/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'setup/images'));
});
gulp.task('build-setup-css', function(){
    utils.buildCssGroup([
        config.paths.src + 'setup/styles/main.scss'
    ], 'setup', 'setup/css/', debugMode);
});

gulp.task('build-setup-js', function(){
    var setupGroup = [
        config.paths.nodes + 'jquery.cookie/jquery.cookie.js',
        config.paths.src + 'vendors/jquery-validation/js/jquery.validate.js',
        config.paths.src + 'vendors/jquery-validate.password/js/jquery.validate.password.js',
        config.paths.src + 'vendors/jquery-test-paths/jquery.test-paths.js'
    ];
    return utils.buildJsGroup(setupGroup, 'setup', 'setup/js', debugMode);
});

gulp.task('watch-setup-js', function() {
    debugMode = true;
    return gulp.watch(config.paths.src + 'setup/**/*.js', ['build-setup-js']);
});

gulp.task('watch-setup-css', function() {
    debugMode = true;
    gulp.watch(config.paths.src + 'setup/**/*.scss', ['build-setup-css']);
});

gulp.task('build-setup', ['copy-setup-images', 'build-setup-css'], function(){
    debugMode = false;
    return gulp.start('build-setup-js');
});