


// TODO: bower, tmp-assets
// TODO: asset, www/assets
var gulp = require('gulp');
var rename = require('gulp-rename');
var clean = require('gulp-clean');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var cssmin = require('gulp-cssmin');
var install = require('gulp-install');
var sass = require('gulp-sass');
var del = require('del');
var config = require('./config.js');

gulp.task('clean:assetsPath', function(done){
    return del([config.paths.build + '/**/*'], done);
});

gulp.task('install-bower-dependencies', function(){
    return gulp.src(['./bower.json'])
        .pipe(install());
});

gulp.task('build-dependencies', ['install-bower-dependencies'], function(){
    gulp.start('build');
    gulp.start('build-css');
});


/**
 * base commands: install, install-assets
 */

gulp.task('install-assets', function(){
    gulp.start('install');
});

gulp.task('install', ['clean:assetsPath'], function(){
    gulp.start('build-dependencies');
});
