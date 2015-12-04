var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-alchemy-embed', function(){
    // copy all dist folder:
    return gulp.src(config.paths.vendors + 'alchemy-embed-medias/dist/**/*')
        .pipe(gulp.dest( config.paths.build + 'vendors/alchemy-embed-medias'));
});