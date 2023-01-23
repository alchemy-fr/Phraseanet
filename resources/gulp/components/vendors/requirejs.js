var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-requirejs', function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'requirejs/require.js'
    ], 'require', 'vendors/requirejs');
});