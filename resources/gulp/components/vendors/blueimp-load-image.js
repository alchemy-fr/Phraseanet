var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-blueimp-load-image', function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'blueimp-load-image/js/load-image.js'
    ], 'load-image', 'vendors/blueimp-load-image');
});