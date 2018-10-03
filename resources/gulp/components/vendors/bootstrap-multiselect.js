var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-bootstrap-multiselect', function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'bootstrap-multiselect/dist/js/bootstrap-multiselect.js'
    ], 'bootstrap-multiselect', 'vendors/bootstrap-multiselect');
});