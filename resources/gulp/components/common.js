var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');

gulp.task('copy-common-images', function(){
    return gulp.src([config.paths.src + 'common/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'common/images'));
});

gulp.task('copy-common-fonts',function(){
    return gulp.src([config.paths.vendors + 'font-awesome/font/*'])
        .pipe(gulp.dest( config.paths.build + 'common/font'));
});

gulp.task('build-common-font-css', ['copy-common-fonts'],function(){
    return gulp.src([config.paths.vendors + 'font-awesome/css/font-awesome-ie7.min.css'])
        .pipe(gulp.dest( config.paths.build + 'common/css'));
});

gulp.task('build-common-css', ['build-common-font-css'],function(){
    return utils.buildCssGroup([
        config.paths.src + 'common/styles/main.scss'
    ], 'common', 'common/css/')
});

gulp.task('build-common-js', function(){
    var commonGroup = [
        // config.paths.dist + 'assets/bootstrap/js/bootstrap.js', // should append no conflict
        config.paths.src + 'vendors/jquery-mousewheel/js/jquery.mousewheel.js',
        // jquery ui date picker langs
        config.paths.vendors + 'jquery-ui/ui/i18n/jquery.ui.datepicker-ar.js',
        config.paths.vendors + 'jquery-ui/ui/i18n/jquery.ui.datepicker-de.js',
        config.paths.vendors + 'jquery-ui/ui/i18n/jquery.ui.datepicker-es.js',
        config.paths.vendors + 'jquery-ui/ui/i18n/jquery.ui.datepicker-fr.js',
        config.paths.vendors + 'jquery-ui/ui/i18n/jquery.ui.datepicker-nl.js',
        config.paths.vendors + 'jquery-ui/ui/i18n/jquery.ui.datepicker-en-GB.js',
        config.paths.vendors + 'jquery.cookie/jquery.cookie.js',
        config.paths.src + 'vendors/jquery-contextmenu/js/jquery.contextmenu_custom.js',
        config.paths.src + 'common/js/jquery.common.js',
        config.paths.src + 'common/js/jquery.tooltip.js',
        config.paths.src + 'common/js/jquery.Dialog.js',
        config.paths.vendors + 'swfobject/swfobject/swfobject.js', // @TODO: should be moved away (embed-bundle)
        config.paths.dist + 'include/jslibs/flowplayer/flowplayer-3.2.13.min.js' // @TODO: should be moved away (embed-bundle)
    ];
    return utils.buildJsGroup(commonGroup, 'common', 'common/js');
});

gulp.task('watch-common-js', function() {
    return gulp.watch(config.paths.src + 'common/**/*.js', ['build-common-js']);
});

gulp.task('watch-common-css', function() {
    gulp.watch(config.paths.src + 'common/**/*.scss', ['build-common-css']);
});

gulp.task('build-common', ['copy-common-images', 'build-common-css'], function(){
    return gulp.start('build-common-js');
});