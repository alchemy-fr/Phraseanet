var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-select2', [], function(){
    return gulp.src([config.paths.nodes + 'select2/**'])
        .pipe(gulp.dest(config.paths.build + 'vendors/select2'));
});
