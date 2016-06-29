var gulp = require('gulp');
var util = require('gulp-util');
var config = require('./config.js');
var debug = require('gulp-debug');
var fs = require('fs');
var utils = require('./utils.js');

gulp.task('watch-css', function(){
    gulp.start('watch-common-css');
    gulp.start('watch-oauth-css');
    gulp.start('watch-thesaurus-css');
    //gulp.start('watch-uploadFlash');
    gulp.start('watch-lightbox-css');
    gulp.start('watch-admin-css');
    gulp.start('watch-report-css');
    gulp.start('watch-account-css');
    // gulp.start('watch-permaview');
    gulp.start('watch-setup-css');
    gulp.start('watch-authentication-css');
});

gulp.task('watch-js', function(){
    gulp.start('watch-common-js');
    // gulp.start('watch-oauth-js');
    gulp.start('watch-thesaurus-js');
    //gulp.start('watch-uploadFlash');
    gulp.start('watch-admin-js');
    gulp.start('watch-report-js');
    // gulp.start('watch-permaview');
    gulp.start('watch-setup-js');
    gulp.start('watch-authentication-js');
    gulp.start('watch-alchemy-embed-medias-js');
    gulp.start('watch-phraseanet-production-client-js');
});

gulp.task('watch', function(){
    gulp.start('watch-css');
    gulp.start('watch-js');
});

var browserSync = require('browser-sync').create();

gulp.task('sync', ['watch'], function(){
    // will open browser in http://localhost:3000/
    browserSync.init({
        proxy: "dev.phraseanet.vb"
    });
    gulp.watch(config.paths.build + '**/*.css').on('change', browserSync.reload);
    gulp.watch(config.paths.build + '**/*.js').on('change', browserSync.reload);
});
