var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('copy-jquery-treeview-images', function(){
    return gulp.src([config.paths.nodes + 'jquery-treeview/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'vendors/jquery-treeview/images'));
});
gulp.task('build-jquery-treeview', ['copy-jquery-treeview-images'], function(){
    // no standalone version used
    /*utils.buildJsGroup([
        config.paths.vendors + 'jquery-treeview/jquery.treeview.async.js'
    ], 'jquery.treeview.async', 'vendors/jquery-treeview');*/
    return utils.buildJsGroup([
        config.paths.nodes + 'jquery-treeview/jquery.treeview.js'
    ], 'jquery.treeview', 'vendors/jquery-treeview');
});