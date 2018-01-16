var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');



gulp.task('build-jquery-file-upload-widget', [], function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'blueimp-file-upload/js/vendor/jquery.ui.widget.js'
    ], 'jquery.ui.widget', 'vendors/jquery-file-upload');
});

gulp.task('build-jquery-file-transport', [], function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'blueimp-file-upload/js/jquery.iframe-transport.js'
    ], 'jquery.iframe-transport', 'vendors/jquery-file-upload');
});

gulp.task('build-jquery-file-upload', ['build-jquery-file-transport', 'build-jquery-file-upload-widget'], function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'blueimp-file-upload/js/jquery.fileupload.js'
    ], 'jquery.fileupload', 'vendors/jquery-file-upload');
});