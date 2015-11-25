var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');

gulp.task('copy-authentication-images', function(){
    return gulp.src([config.paths.src + 'authentication/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'authentication/images'));
});
gulp.task('build-authentication-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'authentication/styles/main.scss'
    ], 'authentication', 'authentication/css/');
});

gulp.task('build-authentication-js', function(){
    var authenticationGroup = [
        config.paths.vendors + 'requirejs/require.js',
        config.paths.dist + 'scripts/apps/login/home/config.js'
    ];
    return utils.buildJsGroup(authenticationGroup, 'authentication', 'authentication/js');
});

gulp.task('watch-authentication-js', function() {
    return gulp.watch(config.paths.src + 'authentication/**/*.js', ['build-authentication-js']);
});

gulp.task('watch-authentication-css', function() {
    gulp.watch(config.paths.src + 'authentication/**/*.scss', ['build-authentication-css']);
});

gulp.task('build-authentication', ['copy-authentication-images', 'build-authentication-css'], function(){
    return gulp.start('build-authentication-js');
});