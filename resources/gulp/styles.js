var gulp = require('gulp');
var util = require('gulp-util');
var rename = require('gulp-rename');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var cssmin = require('gulp-cssmin');
var config = require('./config.js');
var debug = require('gulp-debug');
var sass = require('gulp-sass');
var fs = require('fs');


var buildCssGroup = function(srcGroup, name, dest){
    if( dest === undefined ) {
        dest = name;
    }
    // ensure all required files exists:
    srcGroup.forEach(fs.statSync); //will trow an error if file not found
    console.log('building group:', name, ' > ', config.paths.build + dest)
    return gulp.src(srcGroup)
        .pipe(sass().on('error', sass.logError))
        .pipe(rename(name + '.css'))
        .pipe(gulp.dest(config.paths.build + dest))
        .pipe(cssmin())
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest(config.paths.build + dest));
};

gulp.task('build-css', function () {

    // copy fontawesome fonts and alt stylesheet:
    gulp.src([config.paths.vendors + 'font-awesome/font/*'])
        .pipe(gulp.dest( config.paths.build + 'common/font'));

    gulp.src([config.paths.vendors + 'font-awesome/css/font-awesome-ie7.min.css'])
        .pipe(gulp.dest( config.paths.distVendors + 'common/css'));

    buildCssGroup([config.paths.src + 'common/main.scss'], 'common', 'common/css/');
    buildCssGroup([config.paths.src + 'admin/main.scss'], 'admin', 'admin/css/');
    buildCssGroup([config.paths.src + 'thesaurus/main.scss'], 'thesaurus', 'thesaurus/css/');
    buildCssGroup([config.paths.src + 'prod/main.scss'], 'prod', 'prod/css/');
    buildCssGroup([config.paths.src + 'prod/skin-000000.scss'], 'skin-000000', 'prod/css/');
    buildCssGroup([config.paths.src + 'prod/skin-959595.scss'], 'skin-959595', 'prod/css/');
    buildCssGroup([config.paths.src + 'setup/main.scss'], 'setup', 'setup/css/');
    buildCssGroup([config.paths.src + 'authentication/main.scss'], 'authentication', 'authentication/css/');
    buildCssGroup([config.paths.src + 'account/main.scss'], 'account', 'account/css/');
    buildCssGroup([config.paths.src + 'oauth/main.scss'], 'oauth', 'oauth/css/');

    buildCssGroup([config.paths.src + 'vendors/jquery-ui/dark-hive.scss'], 'dark-hive', 'vendors/jquery-ui/css/');
    buildCssGroup([config.paths.src + 'vendors/jquery-ui/ui-lightness.scss'], 'ui-lightness', 'vendors/jquery-ui/css/');
});