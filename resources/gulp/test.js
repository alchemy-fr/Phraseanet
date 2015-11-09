var gulp = require('gulp');
var qunit = require('gulp-qunit');
var mochaPhantomjs = require('gulp-mocha-phantomjs');

var config = require('./config.js');

var paths = {
    vendors: 'tmp-assets/',
    dist: 'www/assets/'
};


gulp.task('mocha_phantomjs', function () {
    return gulp.src('www/scripts/tests/*.html')
        .pipe(mochaPhantomjs({
            'reporter': 'dot',
            'setting': [
                'loadImages=false'
            ]
        }));
});

gulp.task('qunit', function () {
    return gulp.src('www/include/js/tests/*.html')
        .pipe(qunit());
});


// js fixtures should be present (./bin/developer phraseanet:generate-js-fixtures)
gulp.task('test', ['qunit','mocha_phantomjs']);