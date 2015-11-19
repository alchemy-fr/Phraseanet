var gulp = require('gulp');
var util = require('gulp-util');
var config = require('./config.js');
var debug = require('gulp-debug');
var fs = require('fs');
var utils = require('./utils.js');

gulp.task('watch', function(){
    gulp.start('watch-common');
    gulp.start('watch-oauth');
    gulp.start('watch-prod');
    gulp.start('watch-thesaurus');
    //gulp.start('watch-uploadFlash');
    gulp.start('watch-lightbox');
    gulp.start('watch-admin');
    gulp.start('watch-report');
    gulp.start('watch-account');
    // gulp.start('watch-permaview');
    gulp.start('watch-setup');
    gulp.start('watch-authentication');
});

var browserSync = require('browser-sync').create();
gulp.task('sync', ['watch'], function(){
    // will open browser in http://localhost:3000/
    browserSync.init({
        proxy: "phraseanet-php55-nginx"
    });
    gulp.watch(config.paths.build + '**/*.css').on('change', browserSync.reload);
});
