var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');




gulp.task('build-underscore', function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'underscore/underscore.js'
    ], 'underscore', 'vendors/underscore');
});
gulp.task('build-backbone', ['build-underscore'], function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'backbone/backbone.js'
    ], 'backbone', 'vendors/backbone');
});
