var gulp = require('gulp');
var util = require('gulp-util');
var rename = require('gulp-rename');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var cssmin = require('gulp-cssmin');
var sass = require('gulp-sass');
var config = require('./config.js');
var debug = require('gulp-debug');
var fs = require('fs');


exports.buildJsGroup = function(srcGroup, name, dest){
    if( dest === undefined ) {
        dest = name;
    }
    // ensure all required files exists:
    srcGroup.forEach(fs.statSync); //will trow an error if file not found
    // console.log('building group:', name, ' > ', config.paths.build + dest)
    return gulp.src(srcGroup)
        .pipe(concat(name + '.js', {newLine: ';'}))
        .pipe(gulp.dest( config.paths.build + dest))
        .pipe(uglify().on('error', config.errorHandler('UGLIFY ERROR'))) //util.log))
        .pipe(rename({ extname: '.min.js' }))
        .pipe(gulp.dest( config.paths.build + dest))
};

exports.buildCssGroup = function(srcGroup, name, dest){
    if( dest === undefined ) {
        dest = name;
    }
    // ensure all required files exists:
    srcGroup.forEach(fs.statSync); //will trow an error if file not found
    // console.log('building group:', name, ' > ', config.paths.build + dest)
    return gulp.src(srcGroup)
        .pipe(sass().on('error', sass.logError))
        .pipe(rename(name + '.css'))
        .pipe(gulp.dest(config.paths.build + dest))
        .pipe(cssmin())
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest(config.paths.build + dest));
};