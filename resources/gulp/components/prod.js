var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');
var qunit = require('gulp-qunit');
var debugMode = false;


// prod submodule
gulp.task('build-uploadFlash',  function(){
    var uploadFlashGroup = [
        config.paths.dist + 'include/jslibs/SWFUpload/swfupload.js',
        config.paths.dist + 'include/jslibs/SWFUpload/plugins/swfupload.queue.js'
    ];
    return utils.buildJsGroup(uploadFlashGroup, 'uploadFlash', 'upload/js');
});

gulp.task('copy-prod-images', function(){
    return gulp.src([config.paths.src + 'prod/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'prod/images'));
});

gulp.task('test-prod', function () {
    return gulp.src(config.paths.src + 'prod/js/tests/*.html')
        .pipe(qunit());
});

gulp.task('build-prod', [], function(){
    debugMode = false;
    return gulp.start('copy-prod-images');
});
