var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');

gulp.task('watch-oauth', function() {
    gulp.watch(config.paths.src + 'oauth/**/*.scss', ['build-oauth']);
});

gulp.task('build-oauth', function () {
    return utils.buildCssGroup([config.paths.src + 'oauth/main.scss'], 'oauth', 'oauth/css/');
});