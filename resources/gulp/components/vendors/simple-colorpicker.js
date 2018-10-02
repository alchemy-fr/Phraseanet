var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-simple-colorpicker', function () {
    gulp.start('copy-simple-colorpicker');
});

gulp.task('copy-simple-colorpicker', function () {
    return gulp.src(config.paths.nodes + 'jquery-simplecolorpicker/*')
        .pipe(gulp.dest(config.paths.build + 'vendors/jquery-simplecolorpicker'));
});