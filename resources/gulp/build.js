var gulp = require('gulp');
var util = require('gulp-util');
var config = require('./config.js');
var debug = require('gulp-debug');
var fs = require('fs');
var utils = require('./utils.js');




gulp.task('build', ['build-vendors'], function(){
    gulp.start('build-common');
    gulp.start('build-permaview');
    gulp.start('build-oauth');
    gulp.start('build-prod');
    gulp.start('build-thesaurus');
    gulp.start('build-uploadFlash');
    gulp.start('build-lightbox');
    gulp.start('build-admin');
    gulp.start('build-report');
    gulp.start('build-account');
    gulp.start('build-permaview');
    gulp.start('build-setup');
    gulp.start('build-authentication');
});

// standalone vendors used across application
gulp.task('build-vendors', [
    'build-alchemy-embed',
    'build-phraseanet-production-client',
    'build-bootstrap',
    'build-html5shiv',
    'build-jquery',
    'build-jquery-ui', // will build themes too
    'build-jquery-mobile',
    'build-jquery-galleria',
    'build-jquery-file-upload',
    'build-json2',
    'build-modernizr',
    'build-zxcvbn',
    'build-tinymce',
    'build-backbone',
    'build-i18next',
    'build-bootstrap-multiselect',
    'build-blueimp-load-image',
    'build-geonames-server-jquery-plugin',
    'build-jquery-cookie',
    'build-requirejs',
    'build-jquery-treeview',
    'build-jquery-lazyload',
    'build-jquery-test-paths',
    'build-simple-colorpicker',
    'build-jquery-datetimepicker'
], function () {
});
