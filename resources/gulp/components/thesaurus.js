var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');
var debugMode = false;

gulp.task('copy-thesaurus-images', function(){
    return gulp.src([config.paths.src + 'thesaurus/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'thesaurus/images'));
});
gulp.task('build-thesaurus-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'thesaurus/styles/main.scss'
    ], 'thesaurus', 'thesaurus/css/', debugMode);
});

gulp.task('build-thesaurus-js', function(){
    var thesaurusGroup = [
        config.paths.src + 'vendors/jquery-sprintf/js/jquery.sprintf.1.0.3.js',
        config.paths.src + 'thesaurus/js/win.js',
        config.paths.src + 'thesaurus/js/xmlhttp.js'
    ];
    return utils.buildJsGroup(thesaurusGroup, 'thesaurus', 'thesaurus/js', debugMode);
});

gulp.task('watch-thesaurus-js', function() {
    debugMode = true;
    return gulp.watch(config.paths.src + 'thesaurus/**/*.js', ['build-thesaurus-js']);
});

gulp.task('watch-thesaurus-css', function() {
    debugMode = true;
    gulp.watch(config.paths.src + 'thesaurus/**/*.scss', ['build-thesaurus-css']);
});

gulp.task('build-thesaurus', ['copy-thesaurus-images', 'build-thesaurus-css'], function(){
    return gulp.start('build-thesaurus-js');
});
