var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');
var debugMode = false;

gulp.task('copy-account-images', function(){
    return gulp.src([config.paths.src + 'account/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'account/images'));
});
gulp.task('build-account-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'account/styles/main.scss'
    ], 'account', 'account/css/', debugMode);
});

gulp.task('build-account-js', function(){
    var accountGroup = [
        config.paths.vendors + 'requirejs/require.js',
        config.paths.src + 'account/js/account.js'
    ];
    return utils.buildJsGroup(accountGroup, 'account', 'account/js', debugMode);
});

gulp.task('watch-account-js', function() {
    debugMode = true;
    return gulp.watch(config.paths.src + 'account/**/*.js', ['build-account-js']);
});

gulp.task('watch-account-css', function() {
    debugMode = true;
    gulp.watch(config.paths.src + 'account/**/*.scss', ['build-account-css']);
});

gulp.task('build-account', ['copy-account-images', 'build-account-css'], function(){
    debugMode = false;
    return gulp.start('build-account-js');
});