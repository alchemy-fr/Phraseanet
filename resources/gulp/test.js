var gulp = require('gulp');
var qunit = require('gulp-qunit');
var mochaPhantomjs = require('gulp-mocha-phantomjs');

var config = require('./config.js');

gulp.task('mocha_phantomjs', function () {
    return gulp.src('www/scripts/tests/!*.html')
        .pipe(mochaPhantomjs({
            'reporter': 'dot',
            'setting': [
                'loadImages=false'
            ]
        }));
});

gulp.task('test-components', function () {
    return gulp.start('test-prod');
});

// JS fixtures should be present (./bin/developer phraseanet:generate-js-fixtures)
// Note: fixture are loaded into scripts/tests/fixtures directory using
// bin/developer phraseanet:regenerate-js-fixtures
gulp.task('test', ['test-components']);