var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');

gulp.task('copy-setup-images', function(){
    return gulp.src([config.paths.src + 'setup/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'setup/images'));
});
gulp.task('build-setup-css', function(){
    utils.buildCssGroup([
        config.paths.src + 'setup/styles/main.scss'
    ], 'setup', 'setup/css/');
});

gulp.task('build-setup', ['copy-setup-images', 'build-setup-css'], function(){
    var setupGroup = [
        config.paths.vendors + 'jquery.cookie/jquery.cookie.js',
        config.paths.dist + 'include/jslibs/jquery-validation/jquery.validate.js',
        config.paths.dist + 'include/jslibs/jquery-validate.password/jquery.validate.password.js',
        config.paths.dist + 'include/path_files_tests.jquery.js'
    ];
    return utils.buildJsGroup(setupGroup, 'setup', 'setup/js');
});