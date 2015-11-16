var gulp = require('gulp');
var config = require('./config.js');
var utils = require('./utils.js');



gulp.task('build-css', function () {

    // copy fontawesome fonts and alt stylesheet:
    gulp.src([config.paths.vendors + 'font-awesome/font/*'])
        .pipe(gulp.dest( config.paths.build + 'common/font'));

    gulp.src([config.paths.vendors + 'font-awesome/css/font-awesome-ie7.min.css'])
        .pipe(gulp.dest( config.paths.distVendors + 'common/css'));

    utils.buildCssGroup([config.paths.src + 'common/main.scss'], 'common', 'common/css/');
    utils.buildCssGroup([config.paths.src + 'admin/main.scss'], 'admin', 'admin/css/');
    utils.buildCssGroup([config.paths.src + 'thesaurus/main.scss'], 'thesaurus', 'thesaurus/css/');
    utils.buildCssGroup([config.paths.src + 'prod/main.scss'], 'prod', 'prod/css/');
    utils.buildCssGroup([config.paths.src + 'prod/skin-000000.scss'], 'skin-000000', 'prod/css/');
    utils.buildCssGroup([config.paths.src + 'prod/skin-959595.scss'], 'skin-959595', 'prod/css/');
    utils.buildCssGroup([config.paths.src + 'setup/main.scss'], 'setup', 'setup/css/');
    utils.buildCssGroup([config.paths.src + 'authentication/main.scss'], 'authentication', 'authentication/css/');
    utils.buildCssGroup([config.paths.src + 'account/main.scss'], 'account', 'account/css/');
    utils.buildCssGroup([config.paths.src + 'oauth/main.scss'], 'oauth', 'oauth/css/');

    utils.buildCssGroup([config.paths.src + 'report/main.scss'], 'report', 'report/css/');
    utils.buildCssGroup([config.paths.src + 'report/main-print.scss'], 'print', 'report/css/');

    utils.buildCssGroup([config.paths.src + 'lightbox/main.scss'], 'lightbox', 'lightbox/css/');
    utils.buildCssGroup([config.paths.src + 'lightbox/main-ie6.scss'], 'lightbox-ie6', 'lightbox/css/');
    utils.buildCssGroup([config.paths.src + 'lightbox/main-mobile.scss'], 'lightbox-mobile', 'lightbox/css/');

    utils.buildCssGroup([config.paths.src + 'vendors/jquery-ui/dark-hive.scss'], 'dark-hive', 'vendors/jquery-ui/css/');
    utils.buildCssGroup([config.paths.src + 'vendors/jquery-ui/ui-lightness.scss'], 'ui-lightness', 'vendors/jquery-ui/css/');
});