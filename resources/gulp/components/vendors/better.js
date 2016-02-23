var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-betterjs', [], function(){
    return utils.buildJsGroup([
        config.paths.vendors + 'better.js/build/better.js'
    ], 'better', 'vendors/better');
});