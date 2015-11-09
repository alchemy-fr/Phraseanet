'use strict';

var gulp = require('gulp');
var wrench = require('wrench');

/**
 *  This will load all js or coffee files in the gulp directory
 *  in order to load all gulp tasks
 */
wrench.readdirSyncRecursive('./resources/gulp').filter(function(file) {
    return (/\.(js)$/i).test(file);
}).map(function(file) {
    require('./resources/gulp/' + file);
});


gulp.task('default', ['install-assets']);

