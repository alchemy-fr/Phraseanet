var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');
var debugMode = false;

gulp.task('copy-report-images', function(){
    return gulp.src([config.paths.src + 'report/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'report/images'));
});

gulp.task('build-report-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'report/styles/main.scss'
    ], 'report', 'report/css/', debugMode);
});

gulp.task('build-report-js', function(){
    var reportGroup = [
        config.paths.src + 'report/js/report.js'
    ];
    return utils.buildJsGroup(reportGroup, 'report', 'report/js', debugMode);
});

gulp.task('watch-report-js', function() {
    debugMode = true;
    return gulp.watch(config.paths.src + 'report/**/*.js', ['build-report-js']);
});

gulp.task('watch-report-css', function() {
    debugMode = true;
    gulp.watch(config.paths.src + 'report/**/*.scss', ['build-report-css']);
});

gulp.task('build-report', ['copy-report-images', 'build-report-css'], function(){
    return gulp.start('build-report-js');
});