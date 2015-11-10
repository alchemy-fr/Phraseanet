var gulp = require('gulp');
var util = require('gulp-util');
var rename = require('gulp-rename');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var cssmin = require('gulp-cssmin');
var sass = require('gulp-sass');
var config = require('./config.js');
var debug = require('gulp-debug');
var fs = require('fs');




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

var accountGroup = [
    // '//include/jslibs/jquery.contextmenu_scroll.js',
    //'//assets/jquery.cookie/jquery.cookie.js',
    // '//include/jquery.common.js',
    config.paths.vendors + 'requirejs/require.js',
    config.paths.dist + 'skins/account/account.js'
];

var authenticationGroup = [
    config.paths.vendors + 'requirejs/require.js',
    config.paths.dist + 'scripts/apps/login/home/config.js'
];

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
    config.paths.dist +  'scripts/apps/admin/main/main.js',
    // /assets/requirejs/require.js,/scripts/apps/admin/require.config.js,/scripts/apps/admin/main/main.js
];

var reportGroup = [
    config.paths.dist + 'include/jslibs/jquery.print.js',
    config.paths.dist + 'include/jslibs/jquery.cluetip.js',
    config.paths.dist + 'include/jquery.nicoslider.js',
    config.paths.dist + 'skins/report/report.js'
];

var thesaurusGroup = [
    config.paths.dist + 'skins/thesaurus/win.js',
    config.paths.dist + 'skins/thesaurus/xmlhttp.js',
    config.paths.dist + 'skins/thesaurus/thesaurus.js',
    config.paths.dist + 'skins/thesaurus/sprintf.js'
];

var lightboxGroup = [
    config.paths.dist + 'skins/lightbox/jquery.lightbox.js'
];

var lightboxIE6Group = [
    config.paths.dist + 'skins/lightbox/jquery.lightbox.ie6.js'
];

var uploadFlashGroup = [
    config.paths.dist + 'include/jslibs/SWFUpload/swfupload.js',
    config.paths.dist + 'include/jslibs/SWFUpload/plugins/swfupload.queue.js'
];

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

var permaviewGroup =  [
    config.paths.dist + 'include/jslibs/jquery.mousewheel.js',
    config.paths.dist + 'include/jquery.image_enhancer.js',
    config.paths.vendors + 'swfobject/swfobject/swfobject.js', // @TODO: should be moved away (embed-bundle)
    config.paths.dist + 'include/jslibs/flowplayer/flowplayer-3.2.13.min.js' // @TODO: should be moved away (embed-bundle)
];


var setupGroup = [
    config.paths.vendors + 'jquery.cookie/jquery.cookie.js',
    config.paths.dist + 'include/jslibs/jquery-validation/jquery.validate.js',
    config.paths.dist + 'include/jslibs/jquery-validate.password/jquery.validate.password.js',
    config.paths.dist + 'include/path_files_tests.jquery.js'
]

var buildJsGroup = function(srcGroup, name, dest){
    if( dest === undefined ) {
        dest = name;
    }
    // ensure all required files exists:
    srcGroup.forEach(fs.statSync); //will trow an error if file not found
    console.log('building group:', name, ' > ', config.paths.build + dest)
    return gulp.src(srcGroup)
        .pipe(concat(name + '.js', {newLine: ';'}))
        .pipe(gulp.dest( config.paths.build + dest))
        .pipe(uglify().on('error', config.errorHandler('UGLIFY ERROR'))) //util.log))
        .pipe(rename({ extname: '.min.js' }))
        .pipe(gulp.dest( config.paths.build + dest))
};
var buildCssGroup = function(srcGroup, name, dest){
    if( dest === undefined ) {
        dest = name;
    }
    // ensure all required files exists:
    srcGroup.forEach(fs.statSync); //will trow an error if file not found
    console.log('building group:', name, ' > ', config.paths.build + dest)
    return gulp.src(srcGroup)
        .pipe(sass().on('error', sass.logError))
        .pipe(rename(name + '.css'))
        .pipe(gulp.dest(config.paths.build + dest))
        .pipe(cssmin())
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest(config.paths.build + dest));
};

gulp.task('build', function(){
    //config.paths.dist + 'assets/json2/json2.js', jquery jquery ui
    buildJsGroup([
        config.paths.vendors + 'jquery/jquery.js'
    ], 'jquery', 'vendors/jquery');

    buildJsGroup([
        config.paths.vendors + 'jquery-ui/ui/jquery-ui.js'
    ], 'jquery-ui', 'vendors/jquery-ui');

    buildJsGroup([
        config.paths.vendors + 'json2/json2.js'
    ], 'json2', 'vendors/json2');

    buildJsGroup([
        config.paths.vendors + 'modernizr/modernizr.js'
    ], 'modernizr', 'vendors/modernizr');


    // build libraries loaded with require js:
    // LOGIN
    // login/home/config.js
    buildJsGroup([
        config.paths.vendors + 'backbone-amd/backbone.js'
    ], 'backbone', 'vendors/backbone');
    buildJsGroup([
        config.paths.vendors + 'underscore-amd/underscore.js'
    ], 'underscore', 'vendors/underscore');
    buildJsGroup([
        config.paths.vendors + 'i18next/release/i18next.amd-1.6.3.js'
    ], 'i18next', 'vendors/i18next');
    buildJsGroup([
        config.paths.vendors + 'bootstrap-multiselect/dist/js/bootstrap-multiselect.js'
    ], 'bootstrap-multiselect', 'vendors/bootstrap-multiselect');
    buildJsGroup([
        config.paths.vendors + 'geonames-server-jquery-plugin/jquery.geonames.js'
    ], 'jquery.geonames', 'vendors/jquery.geonames');
    // galleria.html.twig
    buildJsGroup([
        config.paths.vendors + 'jquery-galleria/src/galleria.js'
    ], 'galleria', 'vendors/jquery-galleria');
    buildJsGroup([
        config.paths.vendors + 'jquery-galleria/src/themes/classic/galleria.classic.js'
    ], 'galleria.classic', 'vendors/jquery-galleria');
    // copy css too:
    buildCssGroup([
        config.paths.vendors + 'jquery-galleria/src/themes/classic/galleria.classic.css'
    ], 'galleria.classic', 'vendors/jquery-galleria');
    // galleria.classic.css


    // ADMIN
    // scripts/apps/admin/require.config.js
    buildJsGroup([
        config.paths.vendors + 'jquery-file-upload/js/vendor/jquery.ui.widget.js'
    ], 'jquery.ui.widget', 'vendors/jquery-file-upload');
    buildJsGroup([
        config.paths.vendors + 'jquery.cookie/jquery.cookie.js'
    ], 'jquery.cookie', 'vendors/jquery.cookie');
    buildJsGroup([
        config.paths.vendors + 'jquery.treeview/jquery.treeview.js'
    ], 'jquery.treeview', 'vendors/jquery.treeview');
    buildJsGroup([
        config.paths.vendors + 'blueimp-load-image/js/load-image.js'
    ], 'load-image', 'vendors/blueimp-load-image');
    buildJsGroup([
        config.paths.vendors + 'jquery-file-upload/js/jquery.iframe-transport.js'
    ], 'jquery.iframe-transport', 'vendors/jquery-file-upload');
    buildJsGroup([
        config.paths.vendors + 'jquery-file-upload/js/jquery.fileupload.js'
    ], 'jquery.fileupload', 'vendors/jquery-file-upload');

    // copy tinyMce:
    gulp.src([config.paths.vendors + 'tinymce/**'])
        .pipe(gulp.dest(config.paths.build + 'vendors/tinymce'));

    buildJsGroup([
        config.paths.vendors + 'zxcvbn/dist/zxcvbn.js'
    ], 'zxcvbn', 'vendors/zxcvbn');

    buildJsGroup([
        config.paths.vendors + 'requirejs/require.js'
    ], 'require', 'vendors/requirejs');

    //gulp.start('build-joyride');
    buildJsGroup(commonGroup, 'common', 'common/js');
    buildJsGroup(prodGroup, 'prod', 'prod/js');
    buildJsGroup(thesaurusGroup, 'thesaurus', 'thesaurus/js');
    buildJsGroup(uploadFlashGroup, 'uploadFlash', 'upload/js');
    buildJsGroup(lightboxGroup, 'lightbox', 'lightbox/js');
    buildJsGroup(lightboxIE6Group, 'lightboxIe6', 'lightbox/js');
    buildJsGroup(adminGroup, 'admin', 'admin/js');
    buildJsGroup(reportGroup, 'report', 'report/js');
    buildJsGroup(accountGroup, 'account', 'account/js');
    buildJsGroup(permaviewGroup, 'permaview', 'permaview/js');
    buildJsGroup(setupGroup, 'setup', 'setup/js');
    buildJsGroup(authenticationGroup, 'authentication', 'authentication/js');

    // build mobile group:
    buildJsGroup([
        config.paths.vendors + 'jquery-mobile-bower/js/jquery.mobile-1.3.2.js'
    ], 'jquery-mobile', 'vendors/jquery-mobile');
    buildCssGroup([
        config.paths.vendors + 'jquery-mobile-bower/css/jquery.mobile-1.3.2.css'
    ], 'jquery-mobile', 'vendors/jquery-mobile');





    // uploadflash
});