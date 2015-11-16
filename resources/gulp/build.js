var gulp = require('gulp');
var util = require('gulp-util');
var config = require('./config.js');
var debug = require('gulp-debug');
var fs = require('fs');
var utils = require('./utils.js');

gulp.task('build-common',  function(){
    var commonGroup = [
        // config.paths.dist + 'assets/bootstrap/js/bootstrap.js', // should append no conflict
        config.paths.dist + 'include/jslibs/jquery.mousewheel.js',
        // jquery ui date picker langs
        config.paths.vendors + 'jquery-ui/ui/i18n/jquery.ui.datepicker-ar.js',
        config.paths.vendors + 'jquery-ui/ui/i18n/jquery.ui.datepicker-de.js',
        config.paths.vendors + 'jquery-ui/ui/i18n/jquery.ui.datepicker-es.js',
        config.paths.vendors + 'jquery-ui/ui/i18n/jquery.ui.datepicker-fr.js',
        config.paths.vendors + 'jquery-ui/ui/i18n/jquery.ui.datepicker-nl.js',
        config.paths.vendors + 'jquery-ui/ui/i18n/jquery.ui.datepicker-en-GB.js',
        config.paths.vendors + 'jquery.cookie/jquery.cookie.js',
        config.paths.dist + 'include/jslibs/jquery.contextmenu_scroll.js',
        config.paths.dist + 'include/jquery.common.js',
        config.paths.dist + 'include/jquery.tooltip.js',
        config.paths.dist + 'skins/prod/jquery.Dialog.js',
        config.paths.vendors + 'swfobject/swfobject/swfobject.js', // @TODO: should be moved away (embed-bundle)
        config.paths.dist + 'include/jslibs/flowplayer/flowplayer-3.2.13.min.js' // @TODO: should be moved away (embed-bundle)
    ];
    return utils.buildJsGroup(commonGroup, 'common', 'common/js');
});

gulp.task('build-prod',  function(){
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
gulp.task('build-thesaurus',  function(){
    var thesaurusGroup = [
        config.paths.dist + 'skins/thesaurus/win.js',
        config.paths.dist + 'skins/thesaurus/xmlhttp.js',
        config.paths.dist + 'skins/thesaurus/thesaurus.js',
        config.paths.dist + 'skins/thesaurus/sprintf.js'
    ];
    return utils.buildJsGroup(thesaurusGroup, 'thesaurus', 'thesaurus/js');
});
gulp.task('build-uploadFlash',  function(){
    var uploadFlashGroup = [
        config.paths.dist + 'include/jslibs/SWFUpload/swfupload.js',
        config.paths.dist + 'include/jslibs/SWFUpload/plugins/swfupload.queue.js'
    ];
    return utils.buildJsGroup(uploadFlashGroup, 'uploadFlash', 'upload/js');
});
gulp.task('build-lightbox',  function(){
    var lightboxGroup = [
        config.paths.dist + 'skins/lightbox/jquery.lightbox.js'
    ];

    var lightboxIE6Group = [
        config.paths.dist + 'skins/lightbox/jquery.lightbox.ie6.js'
    ];
    utils.buildJsGroup(lightboxIE6Group, 'lightboxIe6', 'lightbox/js');
    return utils.buildJsGroup(lightboxGroup, 'lightbox', 'lightbox/js');
});

gulp.task('build-admin',  function(){
    var adminGroup = [
        config.paths.vendors + 'underscore-amd/underscore.js',
        config.paths.vendors + 'jquery.treeview/jquery.treeview.js',
        config.paths.dist +  'include/jquery.kb-event.js',
        config.paths.dist +  'skins/admin/template-dialogs.js',
        // loaded via requirejs
        // config.paths.vendors + 'blueimp-load-image/js/load-image.js',
        // config.paths.vendors + 'jquery-file-upload/js/jquery.iframe-transport.js',
        // config.paths.vendors + 'jquery-file-upload/js/jquery.fileupload.js',
        config.paths.vendors + 'requirejs/require.js',
        config.paths.dist +  'scripts/apps/admin/require.config.js',
        config.paths.dist +  'scripts/apps/admin/main/main.js'
    ];
    utils.buildJsGroup(adminGroup, 'admin', 'admin/js');
});

gulp.task('build-report',  function(){
    var reportGroup = [
        config.paths.dist + 'include/jslibs/jquery.print.js',
        config.paths.dist + 'include/jslibs/jquery.cluetip.js',
        config.paths.dist + 'include/jquery.nicoslider.js',
        config.paths.dist + 'skins/report/report.js'
    ];
    return utils.buildJsGroup(reportGroup, 'report', 'report/js');
});

gulp.task('build-account',  function(){
    var accountGroup = [
        config.paths.vendors + 'requirejs/require.js',
        config.paths.dist + 'skins/account/account.js'
    ];
    return utils.buildJsGroup(accountGroup, 'account', 'account/js');
});

gulp.task('build-permaview',  function(){
    var permaviewGroup =  [
        config.paths.dist + 'include/jslibs/jquery.mousewheel.js',
        config.paths.dist + 'include/jquery.image_enhancer.js',
        config.paths.vendors + 'swfobject/swfobject/swfobject.js', // @TODO: should be moved away (embed-bundle)
        config.paths.dist + 'include/jslibs/flowplayer/flowplayer-3.2.13.min.js' // @TODO: should be moved away (embed-bundle)
    ];
    return utils.buildJsGroup(permaviewGroup, 'permaview', 'permaview/js');
});

gulp.task('build-setup',  function(){
    var setupGroup = [
        config.paths.vendors + 'jquery.cookie/jquery.cookie.js',
        config.paths.dist + 'include/jslibs/jquery-validation/jquery.validate.js',
        config.paths.dist + 'include/jslibs/jquery-validate.password/jquery.validate.password.js',
        config.paths.dist + 'include/path_files_tests.jquery.js'
    ];
    return utils.buildJsGroup(setupGroup, 'setup', 'setup/js');
});
gulp.task('build-authentication',  function(){
    var authenticationGroup = [
        config.paths.vendors + 'requirejs/require.js',
        config.paths.dist + 'scripts/apps/login/home/config.js'
    ];
    return utils.buildJsGroup(authenticationGroup, 'authentication', 'authentication/js');
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
    'build-jquery-treeview'
    ], function() {});