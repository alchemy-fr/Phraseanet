var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-json2', [], function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'JSON2/json2.js'
    ], 'json2', 'vendors/json2');
});