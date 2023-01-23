var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');
var debugMode = false;

gulp.task('copy-common-images', function(){
    return gulp.src([config.paths.src + 'common/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'common/images'));
});

gulp.task('copy-common-fonts',function(){
    return gulp.src([config.paths.nodes + 'font-awesome/fonts/*'])
        .pipe(gulp.dest(config.paths.build + 'common/fonts'));
});

gulp.task('copy-common-roboto-fonts', function () {
    return gulp.src([config.paths.src + 'common/styles/fonts/**'])
        .pipe(gulp.dest(config.paths.build + 'common/fonts'));
});

gulp.task('build-common-font-css', ['copy-common-fonts', 'copy-common-roboto-fonts'], function () {
    return gulp.src([config.paths.nodes + 'font-awesome/css/font-awesome.min.css'])
        .pipe(gulp.dest( config.paths.build + 'common/css'));
});

gulp.task('build-common-css', ['build-common-font-css'],function(){
    return utils.buildCssGroup([
        config.paths.src + 'common/styles/main.scss'
    ], 'common', 'common/css/', debugMode)
});

gulp.task('build-common-js', function(){
    var commonGroup = [
        config.paths.src + 'common/js/components/utils.js',
        config.paths.src + 'common/js/components/user.js',
        // config.paths.dist + 'assets/bootstrap/js/bootstrap.js', // should append no conflict
        config.paths.src + 'vendors/jquery-mousewheel/js/jquery.mousewheel.js',
        // jquery ui date picker langs
        config.paths.nodes + 'jquery-ui-datepicker-with-i18n/ui/i18n/jquery.ui.datepicker-ar.js',
        config.paths.nodes + 'jquery-ui-datepicker-with-i18n/ui/i18n/jquery.ui.datepicker-de.js',
        config.paths.nodes + 'jquery-ui-datepicker-with-i18n/ui/i18n/jquery.ui.datepicker-es.js',
        config.paths.nodes + 'jquery-ui-datepicker-with-i18n/ui/i18n/jquery.ui.datepicker-fr.js',
        config.paths.nodes + 'jquery-ui-datepicker-with-i18n/ui/i18n/jquery.ui.datepicker-nl.js',
        config.paths.nodes + 'jquery-ui-datepicker-with-i18n/ui/i18n/jquery.ui.datepicker-en-GB.js',
        config.paths.nodes + 'jquery.cookie/jquery.cookie.js',
        config.paths.src + 'vendors/jquery-contextmenu/js/jquery.contextmenu_custom.js',
        config.paths.src + 'common/js/components/common.js',
        config.paths.src + 'common/js/components/tooltip.js',
        config.paths.src + 'common/js/components/dialog.js',
        config.paths.src + 'common/js/components/utils.js',
        config.paths.src + 'common/js/components/download.js',
        config.paths.src + 'common/js/components/bootstrap-tagsinput.min.js',
    ];
    return utils.buildJsGroup(commonGroup, 'common', 'common/js', debugMode);
});

gulp.task('watch-common-js', function() {
    debugMode = true;
    return gulp.watch(config.paths.src + 'common/**/*.js', ['build-common-js']);
});

gulp.task('watch-common-css', function() {
    debugMode = true;
    gulp.watch(config.paths.src + 'common/**/*.scss', ['build-common-css']);
});

gulp.task('build-common', ['copy-common-images', 'build-common-css'], function(){
    debugMode = false;
    return gulp.start('build-common-js');
});
