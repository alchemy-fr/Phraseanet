var gulp = require('gulp');
var gutil = require('gulp-util');
var rename = require('gulp-rename');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var cssmin = require('gulp-cssmin');
var sass = require('gulp-sass');
var config = require('./config.js');
var debug = require('gulp-debug');
var autoprefixer = require('gulp-autoprefixer');
var fs = require('fs');


exports.buildJsGroup = function(srcGroup, name, dest, debugMode){
    if( dest === undefined ) {
        dest = name;
    }
    // ensure all required files exists:
    srcGroup.forEach(fs.statSync); //will trow an error if file not found
    // console.log('building group:', name, ' > ', config.paths.build + dest)


    if( debugMode === true ) {
        gutil.log(gutil.colors.red('[DEBUG MODE]'), ' "' + name + '" minified version has not been generated');
        return gulp.src(srcGroup)
            .pipe(concat(name + '.js', {newLine: ';'}))
            .pipe(gulp.dest( config.paths.build + dest))
            .pipe(gulp.dest( config.paths.build + dest))
    }

    return gulp.src(srcGroup)
        .pipe(concat(name + '.js', {newLine: ';'}))
        .pipe(gulp.dest( config.paths.build + dest))
        .pipe(uglify({
            compress: {
                drop_console: true
            }
        }).on('error', config.errorHandler('UGLIFY ERROR')))
        .pipe(rename({ extname: '.min.js' }))
        .pipe(gulp.dest( config.paths.build + dest))
};

exports.buildCssGroup = function(srcGroup, name, dest, debugMode){
    if( dest === undefined ) {
        dest = name;
    }
    // ensure all required files exists:
    srcGroup.forEach(fs.statSync); //will trow an error if file not found
    // console.log('building group:', name, ' > ', config.paths.build + dest)


    if( debugMode === true ) {
        gutil.log(gutil.colors.red('[DEBUG MODE]'), ' "' + name + '" minified version has not been generated');
        return gulp.src(srcGroup)
            .pipe(sass().on('error', sass.logError))
            .pipe(autoprefixer())
            .pipe(rename(name + '.css'))
            .pipe(gulp.dest(config.paths.build + dest))
    }

    return gulp.src(srcGroup)
        .pipe(sass().on('error', sass.logError))
        .pipe(autoprefixer())
        .pipe(rename(name + '.css'))
        .pipe(gulp.dest(config.paths.build + dest))
        .pipe(cssmin())
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest(config.paths.build + dest));
};
