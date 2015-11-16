var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');

gulp.task('copy-prod-images', function(){
    // @TODO
    return gulp.src([config.paths.src + 'prod/images/***'])
        .pipe(gulp.dest( config.paths.build + 'prod/images'));
});

gulp.task('build-prod-skin-black', function(){
    return utils.buildCssGroup([
        config.paths.src + 'prod/styles/skin-000000.scss'
    ], 'skin-000000', 'prod/css/');
});

gulp.task('build-prod-skin-grey', function(){
    return utils.buildCssGroup([
        config.paths.src + 'prod/styles/skin-959595.scss'
    ], 'skin-959595', 'prod/css/');
});

gulp.task('build-prod-css', ['build-prod-skin-black', 'build-prod-skin-grey'], function(){
    return utils.buildCssGroup([
        config.paths.src + 'prod/styles/main.scss'
    ], 'prod', 'prod/css/');
});

gulp.task('build-prod', ['copy-prod-images', 'build-prod-css'], function(){
    var prodGroup = [
        config.paths.vendors +  'underscore-amd/underscore.js',
        config.paths.dist + 'include/jslibs/colorpicker/js/colorpicker.js',
        config.paths.dist + 'include/jslibs/jquery.lazyload/jquery.lazyload.1.8.1.js',
        config.paths.vendors + 'humane-js/humane.js', // @TODO > extra files
        config.paths.vendors + 'blueimp-load-image/js/load-image.js', // @TODO > extra files
        config.paths.vendors + 'jquery-file-upload/js/jquery.iframe-transport.js',
        config.paths.vendors + 'jquery-file-upload/js/jquery.fileupload.js',
        config.paths.dist + 'include/jslibs/jquery.form.2.49.js',
        config.paths.dist + 'include/jslibs/jquery.vertical.buttonset.js',
        config.paths.dist + 'include/js/jquery.Selection.js',
        config.paths.dist + 'include/js/jquery.Edit.js',
        config.paths.dist + 'include/js/jquery.lists.js',
        config.paths.dist + 'skins/prod/jquery.Prod.js',
        config.paths.dist + 'skins/prod/jquery.Feedback.js',
        config.paths.dist + 'skins/prod/jquery.Results.js',
        config.paths.dist + 'skins/prod/jquery.main-prod.js',
        config.paths.dist + 'skins/prod/jquery.WorkZone.js',
        config.paths.dist + 'skins/prod/jquery.Alerts.js',
        config.paths.dist + 'skins/prod/jquery.Upload.js',
        config.paths.dist + 'include/jslibs/pixastic.custom.js',
        config.paths.dist + 'skins/prod/ThumbExtractor.js',
        config.paths.dist + 'skins/prod/publicator.js',
        config.paths.dist + 'include/jslibs/jquery.sprintf.1.0.3.js',
        config.paths.dist + 'include/jquery.p4.preview.js',
        config.paths.dist + 'skins/prod/jquery.edit.js',
        config.paths.dist + 'include/jslibs/jquery.color.animation.js',
        config.paths.dist + 'include/jquery.image_enhancer.js',
        config.paths.vendors + 'jquery.treeview/jquery.treeview.js',
        config.paths.vendors + 'jquery.treeview/jquery.treeview.async.js',
        config.paths.vendors + 'fancytree/dist/jquery.fancytree-all.min.js'
    ];
    return utils.buildJsGroup(prodGroup, 'prod', 'prod/js');
});