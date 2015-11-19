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

gulp.task('watch-setup', function() {
    gulp.watch(config.paths.src + 'setup/**/*.scss', ['build-setup-css']);
});

gulp.task('build-setup', ['copy-setup-images', 'build-setup-css'], function(){
    var setupGroup = [
        config.paths.vendors + 'jquery.cookie/jquery.cookie.js',
        config.paths.src + 'vendors/jquery-validation/js/jquery.validate.js',
        config.paths.src + 'vendors/jquery-validate.password/js/jquery.validate.password.js',
        config.paths.src + 'setup/js/path_files_tests.jquery.js'
    ];
    return utils.buildJsGroup(setupGroup, 'setup', 'setup/js');
});