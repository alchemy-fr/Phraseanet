var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');
gulp.task('copy-alchemy-embed', function(){
    // copy all dist folder:
    return gulp.src(config.paths.vendors + 'alchemy-embed-medias/dist/**/*')
        .pipe(gulp.dest( config.paths.build + 'vendors/alchemy-embed-medias'));
});
gulp.task('watch-alchemy-embed-js', function() {
    debugMode = true;
    return gulp.watch(config.paths.vendors + 'alchemy-embed-medias/dist/**/*', ['copy-alchemy-embed']);
});
gulp.task('build-alchemy-embed', function(){
    gulp.start('copy-alchemy-embed');
});