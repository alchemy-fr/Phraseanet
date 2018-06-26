var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-jquery-cookie', function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'jquery.cookie/jquery.cookie.js'
    ], 'jquery.cookie', 'vendors/jquery.cookie');
});