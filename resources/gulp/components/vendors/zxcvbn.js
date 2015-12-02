var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');

gulp.task('build-zxcvbn', [], function(){
    return utils.buildJsGroup([
        config.paths.vendors + 'zxcvbn/dist/zxcvbn.js'
    ], 'zxcvbn', 'vendors/zxcvbn');
});