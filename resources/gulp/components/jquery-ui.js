var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');

gulp.task('build-jquery-ui', [], function(){
    // copy jquery ui assets
    return utils.buildJsGroup([
        config.paths.vendors + 'jquery-ui/ui/jquery-ui.js'
    ], 'jquery-ui', 'vendors/jquery-ui');
});