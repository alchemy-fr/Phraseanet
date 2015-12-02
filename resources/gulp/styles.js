var gulp = require('gulp');
var config = require('./config.js');
var utils = require('./utils.js');



gulp.task('build-css', function () {
    utils.buildCssGroup([config.paths.src + 'oauth/main.scss'], 'oauth', 'oauth/css/');

});