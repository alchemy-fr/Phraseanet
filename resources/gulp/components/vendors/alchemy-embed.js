var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');
var debugMode = false;

// for dev purposes
gulp.task('copy-alchemy-embed-debug', function(){
    debugMode = true;
    gulp.start('copy-alchemy-embed');
});

gulp.task('copy-alchemy-embed', function(){
    // copy all dist folder:
    return gulp.src('vendor/alchemy/embed-bundle/dist/**/*')
        .pipe(gulp.dest( config.paths.build + 'vendors/alchemy-embed-medias'));

    
   /* if( debugMode === true) {
        return gulp.src('vendor/alchemy/embed-bundle/dist/!**!/!*')
            .pipe(gulp.dest( config.paths.build + 'vendors/alchemy-embed-medias'));
    }
    return gulp.src(config.paths.nodes + 'alchemy-embed-medias/dist/!**!/!*')
        .pipe(gulp.dest( config.paths.build + 'vendors/alchemy-embed-medias'));*/
});
gulp.task('watch-alchemy-embed-js', function() {
    debugMode = true;
    // in dev mode, watch composer's vendor path:
    return gulp.watch('vendor/alchemy/embed-bundle/dist/**/*', ['copy-alchemy-embed']);
});
gulp.task('build-alchemy-embed', function(){
    gulp.start('copy-alchemy-embed');
});
