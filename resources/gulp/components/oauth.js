var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');
var debugMode = false;
gulp.task('watch-oauth-css', function() {
    debugMode = true;
    gulp.watch(config.paths.src + 'oauth/**/*.scss', ['build-oauth']);
});

gulp.task('build-oauth', function () {
    debugMode = false;
    return utils.buildCssGroup([config.paths.src + 'oauth/main.scss'], 'oauth', 'oauth/css/', debugMode);
});