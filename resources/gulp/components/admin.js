var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');
var debugMode = false;

gulp.task('copy-admin-images', function(){
    return gulp.src([config.paths.src + 'admin/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'admin/images'));
});
gulp.task('build-admin-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'admin/styles/main.scss'
    ], 'admin', 'admin/css/', debugMode);
});

gulp.task('build-admin-js', function(){
    var adminGroup = [
        config.paths.nodes + 'underscore/underscore.js',
        config.paths.nodes + 'jquery-treeview/jquery.treeview.js',
        // config.paths.vendors + 'jquery-file-upload/js/vendor/jquery.ui.widget.js',
        // config.paths.vendors + 'jquery-file-upload/js/jquery.iframe-transport.js',
        // config.paths.vendors + 'jquery-file-upload/js/jquery.fileupload.js',
        config.paths.src +  'admin/js/jquery.kb-event.js',
        config.paths.src +  'admin/js/template-dialogs.js',
        config.paths.nodes + 'requirejs/require.js',
        config.paths.dist +  'scripts/apps/admin/require.config.js',
        config.paths.dist +  'scripts/apps/admin/main/main.js'
    ];
    utils.buildJsGroup(adminGroup, 'admin', 'admin/js', debugMode);
});

gulp.task('watch-admin-js', function() {
    debugMode = true;
    return gulp.watch(config.paths.src + 'admin/**/*.js', ['build-admin-js']);
});

gulp.task('watch-admin-css', function() {
    debugMode = true;
    gulp.watch(config.paths.src + 'admin/**/*.scss', ['build-admin-css']);
});

gulp.task('build-admin', ['copy-admin-images', 'build-admin-css'], function(){
    debugMode = false;
    return gulp.start('build-admin-js');
});