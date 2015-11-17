var gulp = require('gulp');
var util = require('gulp-util');
var config = require('./config.js');
var debug = require('gulp-debug');
var fs = require('fs');
var utils = require('./utils.js');


//@TODO > submodule of prod
gulp.task('build-uploadFlash',  function(){
    var uploadFlashGroup = [
        config.paths.dist + 'include/jslibs/SWFUpload/swfupload.js',
        config.paths.dist + 'include/jslibs/SWFUpload/plugins/swfupload.queue.js'
    ];
    return utils.buildJsGroup(uploadFlashGroup, 'uploadFlash', 'upload/js');
});

//@TODO > submodule of prod
gulp.task('build-permaview',  function(){
    var permaviewGroup =  [
        config.paths.dist + 'include/jslibs/jquery.mousewheel.js',
        config.paths.dist + 'include/jquery.image_enhancer.js',
        config.paths.vendors + 'swfobject/swfobject/swfobject.js', // @TODO: should be moved away (embed-bundle)
        config.paths.dist + 'include/jslibs/flowplayer/flowplayer-3.2.13.min.js' // @TODO: should be moved away (embed-bundle)
    ];
    return utils.buildJsGroup(permaviewGroup, 'permaview', 'permaview/js');
});

gulp.task('build', ['build-vendors'], function(){
    gulp.start('build-common');
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
    'build-bootstrap',
    'build-jquery',
    'build-jquery-ui',
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
    'build-jquery-lazyload'
    ], function() {});