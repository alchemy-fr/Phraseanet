var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');

gulp.task('copy-report-images', function(){
    return gulp.src([config.paths.src + 'report/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'report/images'));
});

gulp.task('build-report-print-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'report/styles/main-print.scss'
    ], 'print', 'report/css/');
});

gulp.task('build-report-css', ['build-report-print-css'], function(){
    return utils.buildCssGroup([
        config.paths.src + 'report/styles/main.scss'
    ], 'report', 'report/css/');
});

gulp.task('build-report', ['copy-report-images', 'build-report-css'], function(){
    var reportGroup = [
        config.paths.dist + 'include/jslibs/jquery.print.js',
        config.paths.dist + 'include/jslibs/jquery.cluetip.js',
        config.paths.dist + 'include/jquery.nicoslider.js',
        config.paths.src + 'report/js/report.js'
    ];
    return utils.buildJsGroup(reportGroup, 'report', 'report/js');
});