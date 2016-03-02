var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');
var qunit = require('gulp-qunit');
var debugMode = false;


// prod submodule
gulp.task('build-uploadFlash',  function(){
    var uploadFlashGroup = [
        config.paths.dist + 'include/jslibs/SWFUpload/swfupload.js',
        config.paths.dist + 'include/jslibs/SWFUpload/plugins/swfupload.queue.js'
    ];
    return utils.buildJsGroup(uploadFlashGroup, 'uploadFlash', 'upload/js');
});

gulp.task('copy-prod-skin-black-images', function(){
    return gulp.src([
            config.paths.src + 'prod/skins/000000/images/**/*'
    ])
        .pipe(gulp.dest( config.paths.build + 'prod/skins/000000/images'));
});

gulp.task('copy-prod-skin-grey-images', function(){
    return gulp.src([
            config.paths.src + 'prod/skins/959595/images/**/*'
        ])
        .pipe(gulp.dest( config.paths.build + 'prod/skins/959595/images'));
});

gulp.task('copy-prod-skin-white-images', function(){
    return gulp.src([
            config.paths.src + 'prod/skins/FFFFFF/images/**/*'
        ])
        .pipe(gulp.dest( config.paths.build + 'prod/skins/FFFFFF/images'));
});

gulp.task('copy-prod-images', function(){
    return gulp.src([config.paths.src + 'prod/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'prod/images'));
});

gulp.task('build-prod-skin-black', ['copy-prod-skin-black-images'], function(){
    return utils.buildCssGroup([
        config.paths.src + 'prod/skins/000000/skin-000000.scss'
    ], 'skin-000000', 'prod/skins/000000/', debugMode);
});

gulp.task('build-prod-skin-grey', ['copy-prod-skin-grey-images'], function(){
    return utils.buildCssGroup([
        config.paths.src + 'prod/skins/959595/skin-959595.scss'
    ], 'skin-959595', 'prod/skins/959595/', debugMode);
});

gulp.task('build-prod-skin-white', ['copy-prod-skin-white-images'], function(){
    return utils.buildCssGroup([
        config.paths.src + 'prod/skins/FFFFFF/skin-FFFFFF.scss'
    ], 'skin-FFFFFF', 'prod/skins/FFFFFF/', debugMode);
});

gulp.task('build-prod-css', ['build-prod-skin-black', 'build-prod-skin-grey', 'build-prod-skin-white'], function(){
    return utils.buildCssGroup([
        config.paths.src + 'prod/styles/main.scss'
    ], 'prod', 'prod/css/', debugMode);
});
gulp.task('build-prod-js', function(){
    var prodGroup = [
        config.paths.vendors +  'underscore-amd/underscore.js',
        config.paths.src + 'vendors/colorpicker/js/colorpicker.js',
        config.paths.vendors +  'jquery.lazyload/jquery.lazyload.js',
        config.paths.vendors + 'humane-js/humane.js', // @TODO > extra files
        config.paths.vendors + 'blueimp-load-image/js/load-image.js', // @TODO > extra files
        config.paths.vendors + 'jquery-file-upload/js/jquery.iframe-transport.js',
        config.paths.vendors + 'jquery-file-upload/js/jquery.fileupload.js',
        config.paths.vendors + 'geonames-server-jquery-plugin/jquery.geonames.js',
        config.paths.src + 'prod/js/core/lists.js',
        config.paths.src + 'prod/js/core/selectable.js',
        config.paths.src + 'prod/js/core/alert.js',

        config.paths.src + 'prod/js/components/search/search.js',
        config.paths.src + 'prod/js/components/search/search-result.js',
        // config.paths.src + 'prod/js/components/publication.js',
        config.paths.src + 'prod/js/components/workzone/workzone.js',
        config.paths.src + 'prod/js/components/workzone/workzone-basket.js',
        config.paths.src + 'prod/js/components/workzone/workzone-facets.js',
        config.paths.src + 'prod/js/components/workzone/workzone-thesaurus.js',
        // config.paths.src + 'prod/js/components/cgu.js',
        // config.paths.src + 'prod/js/components/preferences.js',
        config.paths.src + 'prod/js/components/record/editable-record.js',
        config.paths.src + 'prod/js/components/push/push.js',
        config.paths.src + 'prod/js/prod.js',
        config.paths.src + 'prod/js/components/upload/upload.js',
        // config.paths.src + 'prod/js/components/video-editor.js',
        config.paths.src + 'vendors/jquery-sprintf/js/jquery.sprintf.1.0.3.js',
        config.paths.src + 'prod/js/components/preview/preview.js',
        config.paths.src + 'prod/js/components/editor/record-editor.js',
        config.paths.src + 'prod/js/jquery.color.animation.js',
        config.paths.src + 'vendors/jquery-image-enhancer/js/jquery.image_enhancer.js',
        config.paths.vendors + 'jquery-treeview/jquery.treeview.js',
        config.paths.vendors + 'jquery-treeview/jquery.treeview.async.js',
        config.paths.vendors + 'fancytree/dist/jquery.fancytree-all.min.js'
    ];
    return utils.buildJsGroup(prodGroup, 'prod', 'prod/js', debugMode);
});

gulp.task('test-prod', function () {
    return gulp.src(config.paths.src + 'prod/js/tests/*.html')
        .pipe(qunit());
});

gulp.task('watch-prod-js', function() {
    debugMode = true;
    return gulp.watch(config.paths.src + 'prod/**/*.js', ['build-prod-js']);
});

gulp.task('watch-prod-css', function() {
    debugMode = true;
    return gulp.watch(config.paths.src + 'prod/**/*.scss', ['build-prod-css']);
});

gulp.task('build-prod', ['copy-prod-images', 'build-prod-css'], function(){
    debugMode = false;
    return gulp.start('build-prod-js');
});
