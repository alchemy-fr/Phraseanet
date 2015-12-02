var gulp = require('gulp');
var config = require('../../config.js');
var utils = require('../../utils.js');



gulp.task('build-jquery-image-enhancer-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'vendors/jquery-image-enhancer/styles/jquery.image_enhancer.scss'
    ], 'jquery-image-enhancer', 'vendors/jquery-image-enhancer');
});

gulp.task('build-jquery-image-enhancer', ['build-jquery-image-enhancer-css'], function(){
    return utils.buildJsGroup([
        config.paths.src + 'vendors/jquery-image-enhancer/js/jquery.image_enhancer.js'
    ], 'jquery-image-enhancer', 'vendors/jquery-image-enhancer');
});