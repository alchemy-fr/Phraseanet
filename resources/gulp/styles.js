var gulp = require('gulp');
var config = require('./config.js');
var utils = require('./utils.js');



gulp.task('build-css', function () {
    utils.buildCssGroup([config.paths.src + 'oauth/main.scss'], 'oauth', 'oauth/css/');
    utils.buildCssGroup([config.paths.src + 'vendors/jquery-ui/dark-hive.scss'], 'dark-hive', 'vendors/jquery-ui/css/');
    utils.buildCssGroup([config.paths.src + 'vendors/jquery-ui/ui-lightness.scss'], 'ui-lightness', 'vendors/jquery-ui/css/');
});