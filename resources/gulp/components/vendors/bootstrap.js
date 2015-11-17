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
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-transition.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-alert.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-modal.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-dropdown.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-scrollspy.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-tab.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-tooltip.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-popover.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-button.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-collapse.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-carousel.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-typeahead.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-affix.js'];

    gulp.src(btSource)
        .pipe(concat('bootstrap.js'))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/js'))
        .pipe(uglify())
        .pipe(rename({ extname: '.min.js' }))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/js'))
});
gulp.task('bootstrap-assets', function () {
    gulp.src([config.paths.vendors + 'bootstrap-sass/vendor/assets/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/img'));

});
gulp.task('build-bootstrap', ['bootstrap-assets', 'bootstrap-js'], function () {
    // build standalone version (not used, see: Common Component)
    gulp.src(config.paths.vendors + 'bootstrap-sass/vendor/assets/stylesheets/bootstrap.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/css/'))
        .pipe(cssmin())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/css'));
    gulp.src(config.paths.vendors + 'bootstrap-sass/vendor/assets/stylesheets/bootstrap-responsive.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/css/'))
        .pipe(cssmin())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/css'));
});