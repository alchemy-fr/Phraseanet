var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-jquery', function(){
    return utils.buildJsGroup([
        config.paths.vendors + 'jquery/dist/jquery.js'
    ], 'jquery', 'vendors/jquery');
});