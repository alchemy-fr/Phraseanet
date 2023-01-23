var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');
var debugMode = false;

// for dev purposes
gulp.task('copy-phraseanet-production-client-debug', function(){
    debugMode = true;
    gulp.start('copy-phraseanet-production-client');
});

gulp.task('copy-phraseanet-production-client', function(){
    // copy all dist folder:

    return gulp.src('Phraseanet-production-client/dist/**/*')
        .pipe(gulp.dest( config.paths.build + 'production'));
});
gulp.task('watch-phraseanet-production-client-js', function() {
    debugMode = true;
    // in dev mode, watch composer's vendor path:
    return gulp.watch('node_modules/phraseanet-production-client/dist/**/*', ['copy-phraseanet-production-client']);
});
gulp.task('build-phraseanet-production-client', function(){
    gulp.start('copy-phraseanet-production-client');
});
