var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-pusher-js', [], function(){
    return gulp.src([config.paths.nodes + 'pusher-js/dist/web/**'])
        .pipe(gulp.dest(config.paths.build + 'vendors/pusher-js'));
});
