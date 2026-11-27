var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-jquery', function(){
    return utils.buildJsGroup([
        config.paths.nodes + 'jquery/dist/jquery.js',
        config.paths.nodes + 'jquery-migrate/dist/jquery-migrate.js',
        config.paths.src + 'vendors/jquery-migrate/jquery-migrate-mute.js'
    ], 'jquery', 'vendors/jquery');
});
