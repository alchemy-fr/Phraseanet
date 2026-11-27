var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-jquery', function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'jquery/dist/jquery.js',
        // mute must be set before jquery-migrate loads to also silence its load-time warnings
        config.paths.src + 'vendors/jquery-migrate/jquery-migrate-mute.js',
        config.paths.nodes + 'jquery-migrate/dist/jquery-migrate.js'
    ], 'jquery', 'vendors/jquery');
});
