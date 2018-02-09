var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-i18next', function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'i18next-clientscript/release/i18next.amd-1.6.0.js'
    ], 'i18next', 'vendors/i18next');
});