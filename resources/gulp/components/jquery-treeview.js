var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');

gulp.task('build-jquery-treeview', function(){
    return utils.buildJsGroup([
        config.paths.vendors + 'jquery.treeview/jquery.treeview.js'
    ], 'jquery.treeview', 'vendors/jquery.treeview');
});