var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');

gulp.task('copy-admin-images', function(){
    return gulp.src([config.paths.src + 'admin/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'admin/images'));
});
gulp.task('build-admin-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'admin/styles/main.scss'
    ], 'admin', 'admin/css/');
});

gulp.task('build-admin', ['copy-admin-images', 'build-admin-css'], function(){
    var adminGroup = [
        config.paths.vendors + 'underscore-amd/underscore.js',
        config.paths.vendors + 'jquery.treeview/jquery.treeview.js',
        config.paths.dist +  'include/jquery.kb-event.js',
        config.paths.src +  'admin/js/template-dialogs.js',
        config.paths.vendors + 'requirejs/require.js',
        config.paths.dist +  'scripts/apps/admin/require.config.js',
        config.paths.dist +  'scripts/apps/admin/main/main.js'
    ];
    utils.buildJsGroup(adminGroup, 'admin', 'admin/js');
});