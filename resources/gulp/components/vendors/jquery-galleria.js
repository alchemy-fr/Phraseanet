var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-galleria-css', function(){
    return utils.buildCssGroup([
        config.paths.nodes + 'galleria/src/themes/classic/galleria.classic.css'
    ], 'galleria.classic', 'vendors/jquery-galleria');
});
gulp.task('build-galleria-js-classic', function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'galleria/src/themes/classic/galleria.classic.js'
    ], 'galleria.classic', 'vendors/jquery-galleria');
});

gulp.task('build-galleria-js', ['build-galleria-js-classic'], function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'galleria/src/galleria.js'
    ], 'galleria', 'vendors/jquery-galleria');
});

gulp.task('build-jquery-galleria', ['build-galleria-js', 'build-galleria-css'], function(){
    // copy jquery mobile assets
    return gulp.src(config.paths.nodes + 'galleria/src/themes/classic/!(*.js|*.map|*.css|*.html)')
        .pipe(gulp.dest( config.paths.build + 'vendors/jquery-galleria'));
});