var gulp = require('gulp');
var sass = require('gulp-sass');
var uglify = require('gulp-uglify');
var cssmin = require('gulp-cssmin');
var concat = require('gulp-concat');
var rename = require('gulp-rename');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('bootstrap-js', function () {
    var btSource = [
        config.paths.nodes + 'bootstrap-sass/js/bootstrap-transition.js',
        config.paths.nodes + 'bootstrap-sass/js/bootstrap-alert.js',
        config.paths.nodes + 'bootstrap-sass/js/bootstrap-modal.js',
        config.paths.nodes + 'bootstrap-sass/js/bootstrap-dropdown.js',
        config.paths.nodes + 'bootstrap-sass/js/bootstrap-scrollspy.js',
        config.paths.nodes + 'bootstrap-sass/js/bootstrap-tab.js',
        config.paths.nodes + 'bootstrap-sass/js/bootstrap-tooltip.js',
        config.paths.nodes + 'bootstrap-sass/js/bootstrap-popover.js',
        config.paths.nodes + 'bootstrap-sass/jss/bootstrap-button.js',
        config.paths.nodes + 'bootstrap-sass/js/bootstrap-collapse.js',
        config.paths.nodes + 'bootstrap-sass/js/bootstrap-carousel.js',
        config.paths.nodes + 'bootstrap-sass/js/bootstrap-typeahead.js',
        config.paths.nodes + 'bootstrap-sass/js/bootstrap-affix.js'];

    gulp.src(btSource)
        .pipe(concat('bootstrap.js'))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/js'))
        .pipe(uglify())
        .pipe(rename({ extname: '.min.js' }))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/js'))
});
gulp.task('bootstrap-assets', function () {
    gulp.src([config.paths.nodes + 'bootstrap-sass/img/**/*'])
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/img'));

});
gulp.task('build-bootstrap', ['bootstrap-assets', 'bootstrap-js'], function () {
    // build standalone version (not used, see: Common Component)
    gulp.src(config.paths.nodes + 'bootstrap-sass/bootstrap-2.3.2.css')
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/css/'))
        .pipe(cssmin())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/css'));
    gulp.src([
        config.paths.nodes + 'bootstrap-sass/bootstrap-responsive-2.3.2.css',
        config.paths.nodes + 'bootstrap-sass/lib/_responsive-utilities.scss'
    ])
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/css/'))
        .pipe(cssmin())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/css'));
});