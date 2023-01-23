var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-geonames-server-jquery-plugin', function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'geonames-server-jquery-plugin/jquery.geonames.js'
    ], 'jquery.geonames', 'vendors/jquery.geonames');
});