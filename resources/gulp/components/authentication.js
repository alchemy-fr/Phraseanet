var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');
var debugMode = false;

gulp.task('copy-authentication-images', function(){
    return gulp.src([config.paths.src + 'authentication/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'authentication/images'));
});
gulp.task('build-authentication-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'authentication/styles/main.scss'
    ], 'authentication', 'authentication/css/', debugMode);
});

gulp.task('build-authentication-js', function(){
    var authenticationGroup = [
        config.paths.nodes + 'requirejs/require.js',
        config.paths.dist + 'scripts/apps/login/home/config.js'
    ];
    return utils.buildJsGroup(authenticationGroup, 'authentication', 'authentication/js', debugMode);
});

gulp.task('watch-authentication-js', function() {
    debugMode = true;
    return gulp.watch(config.paths.src + 'authentication/**/*.js', ['build-authentication-js']);
});

gulp.task('watch-authentication-css', function() {
    debugMode = true;
    gulp.watch(config.paths.src + 'authentication/**/*.scss', ['build-authentication-css']);
});

gulp.task('build-authentication', ['copy-authentication-images', 'build-authentication-css'], function(){
    debugMode = false;
    return gulp.start('build-authentication-js');
});