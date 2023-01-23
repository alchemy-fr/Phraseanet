


// TODO: bower, tmp-assets
// TODO: asset, www/assets
var gulp = require('gulp');
var rename = require('gulp-rename');
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

gulp.task('build-dependencies', function () {
    gulp.start('build');
    gulp.start('build-css');
});
gulp.task('init-plugins-folder', function(){
    if( !config.checkPath('plugins', true)) {
        // something to do in plugins folder?
    }
});


/**
 * base commands: install, install-assets
 */

gulp.task('install-assets', function(){
    gulp.start('install');
});

gulp.task('install', ['clean:assetsPath'], function(){

    // ensure plugins path exists
    gulp.start('init-plugins-folder');
    gulp.start('build-dependencies');
});
