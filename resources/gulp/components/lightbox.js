var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');
var debugMode = false;

gulp.task('copy-lightbox-images', function(){
    return gulp.src([config.paths.src + 'lightbox/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'lightbox/images'));
});

gulp.task('build-lightbox-mobile-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'lightbox/styles/main-mobile.scss'
    ], 'lightbox-mobile', 'lightbox/css/', debugMode);
});

gulp.task('build-lightbox-css', ['build-lightbox-mobile-css'], function(){
    return utils.buildCssGroup([
        config.paths.src + 'lightbox/styles/main.scss'
    ], 'lightbox', 'lightbox/css/', debugMode)
});

gulp.task('watch-lightbox-css', function() {
    debugMode = true;
    gulp.watch(config.paths.src + 'lightbox/**/*.scss', ['build-lightbox-css']);
});

gulp.task('build-lightbox', ['copy-lightbox-images'], function(){
    debugMode = false;
    return gulp.start('build-lightbox-css');
});
