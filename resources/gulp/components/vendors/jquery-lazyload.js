var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-jquery-lazyload', function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'jquery-lazyload/jquery.lazyload.js'
    ], 'jquery.lazyload', 'vendors/jquery.lazyload');
});