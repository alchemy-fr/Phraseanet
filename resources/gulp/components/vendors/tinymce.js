var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-tinymce', [], function(){
    return gulp.src([config.paths.nodes + 'tinymce/**'])
        .pipe(gulp.dest(config.paths.build + 'vendors/tinymce'));
});