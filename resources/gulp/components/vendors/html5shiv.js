var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-html5shiv', function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'html5shiv/dist/html5shiv.js'
    ], 'html5shiv', 'vendors/html5shiv');
});