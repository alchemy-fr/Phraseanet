var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');
var debugMode = false;

// for dev purposes
gulp.task('copy-alchemy-embed-medias-debug', function(){
    debugMode = true;
    gulp.start('copy-alchemy-embed-medias');
});

gulp.task('copy-alchemy-embed-medias', function(){
    // copy all dist folder:

    return gulp.src('node_modules/alchemy-embed-medias/dist/**/*')
        .pipe(gulp.dest( config.paths.build + 'vendors/alchemy-embed-medias'));
});
gulp.task('watch-alchemy-embed-medias-js', function() {
    debugMode = true;
    // in dev mode, watch composer's vendor path:
    return gulp.watch('node_modules/alchemy-embed-medias/dist/**/*', ['copy-alchemy-embed-medias']);
});
gulp.task('build-alchemy-embed-medias', function(){
    gulp.start('copy-alchemy-embed-medias');
});
