var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-swfobject', function(){
    // copy all dist folder:
    return gulp.src(config.paths.vendors + 'swfobject/swfobject/swfobject.js')
        .pipe(gulp.dest( config.paths.build + 'vendors/swfobject'));
});