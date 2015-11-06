// TODO: bower, tmp-assets
// TODO: asset, www/assets
var gulp = require('gulp');
var rename = require('gulp-rename');
var clean = require('gulp-clean');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var qunit = require('gulp-qunit');
var mochaPhantomjs = require('gulp-mocha-phantomjs');
var cssmin = require('gulp-cssmin');
var install = require('gulp-install');
var sass = require('gulp-sass');
var del = require('del');

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

gulp.task('clean:vendor', function(done){
    return del([paths.dist + '/**/*'], done);
});


var lib    = require('bower-files')({
    overrides: {
        'backbone-amd': {
            main: ['./backbone.js', 'LICENSE']
        },
        'font-awesome': {
            main: ['css/*', 'font/*']
        },
        'humane-js': {
            main: ['humane.js', 'themes/libnotify.css']
        },
        'jquery': {
            main: 'jquery.js'
        },
        'jquery.treeview': {
            main: ['images/*',
                'jquery.treeview*']
        },
        'jquery-mobile-bower': {
            main: ['css/jquery.mobile-1.3.2.css', 'js/jquery.mobile-1.3.2.js']
        },
        'json2': {
            main: ['json2.js']
        },
        'modernizr': {
            main: 'modernizr.js'
        },
        'tinymce': {
            main: ['plugins/**',
                'skins/**',
                'themes/**',
                '*.js',
                'changelog.txt',
                'license.txt']
        },
        'underscore-amd': {
            main:
                'underscore.js'
        },
        zxcvbn: {
            main: [/* ignore, will be copied manually for legacy code compatibility */]
        },
        'jquery-galleria': {
            main: [/* ignore, will be copied manually for legacy code compatibility */]
        },
        'bootstrap-multiselect': {
            main: [/* ignore, will be copied manually for legacy code compatibility */]
        },
        'fancytree': {
            main: [/* ignore, will be copied manually for legacy code compatibility */]
        },
        'jquery-ui': {
            main: [/* ignore, will be copied manually for legacy code compatibility */]
        },
        'swfobject': {
            main: [/* ignore, will be copied manually for legacy code compatibility */]
        },
        'blueimp-load-image': {
            main: [/* ignore, will be copied manually for legacy code compatibility */]
        },
        'jquery-file-upload': {
            main: [/* ignore, will be copied manually for legacy code compatibility */]
        },
        'i18next': {
          main: [/* ignore, will be copied manually for legacy code compatibility */]
        }
    }
});

gulp.task('copy-dev-vendors', function(){
    // @TODO copy:
    // chai,js-fixtures, mocha sinon-chai, sinonjs, squire, qunit
    gulp.src(paths.vendors + 'chai/chai.js')
        .pipe(gulp.dest( paths.dist + 'chai/'));

    gulp.src(paths.vendors + 'js-fixtures/fixtures.js')
        .pipe(gulp.dest( paths.dist + 'js-fixtures/'));

    gulp.src([paths.vendors + 'mocha/mocha.js', paths.vendors + 'mocha/mocha.css'])
        .pipe(gulp.dest( paths.dist + 'mocha/'));

    gulp.src(paths.vendors + 'sinon-chai/lib/sinon-chai.js')
        .pipe(gulp.dest( paths.dist + 'sinon-chai/'));

    gulp.src(paths.vendors + 'sinonjs/sinon.js')
        .pipe(gulp.dest( paths.dist + 'sinonjs/'));

    gulp.src(paths.vendors + 'squire/src/Squire.js')
        .pipe(gulp.dest( paths.dist + 'squire/'));

    gulp.src([paths.vendors + 'qunit/qunit/qunit.js', paths.vendors + 'qunit/qunit/qunit.css'])
        .pipe(gulp.dest( paths.dist + 'qunit/'));

    gulp.src(paths.vendors + 'qunit/addons/phantomjs/*')
        .pipe(gulp.dest( paths.dist + 'qunit/addons/phantomjs'));
});

gulp.task('copy-vendor-via-bower', function () {
    var vendorConfig = {
        'jquery-mobile-bower/css': {
            dirname: 'jquery-mobile'
        },
        'jquery-mobile-bower/js': {
            dirname: 'jquery-mobile'
        }
    };

    return gulp.src(lib.ext().files, { base: paths.vendors })
        .pipe(rename(function (path) {
            if( vendorConfig[path.dirname] !== undefined ) {
                // console.log('reading path', path)
                var cuConf = vendorConfig[path.dirname];
                path.dirname = cuConf.dirname;
            }
            return path;
        }))
        .pipe(gulp.dest(paths.dist));
});

// copy additionnal assets from vendors to match old legacy assets:
gulp.task('copy-vendors', ['copy-vendor-via-bower'],function () {
    gulp.src([paths.vendors + 'fancytree/dist/skin-win8/**/*']) //, paths.vendors + 'fancytree/dist/jquery.fancytree-all.min
        .pipe(gulp.dest( paths.dist + 'fancytree/dist/skin-win8'))

    gulp.src(paths.vendors + 'jquery-ui/ui/jquery-ui.js')
        .pipe(gulp.dest( paths.dist + 'jquery.ui/'));

    gulp.src(paths.vendors + 'jquery-ui/themes/base/*.css')
        .pipe(gulp.dest( paths.dist + 'jquery.ui/'));

    gulp.src(paths.vendors + 'jquery-ui/ui/i18n/*')
        .pipe(gulp.dest( paths.dist + 'jquery.ui/i18n'));

    gulp.src(paths.vendors + 'jquery-ui/themes/base/images/*')
        .pipe(gulp.dest( paths.dist + 'jquery.ui/images'));

    gulp.src(paths.vendors + 'swfobject/swfobject/swfobject.js')
        .pipe(gulp.dest( paths.dist + 'swfobject'));


    gulp.src([paths.vendors + 'jquery-file-upload/js/*', paths.vendors + 'jquery-file-upload/js/vendor/*', paths.vendors + 'jquery-file-upload/css/*'])
        .pipe(gulp.dest( paths.dist + 'jquery-file-upload'));

    gulp.src(paths.vendors + 'blueimp-load-image/js/*.js')
        .pipe(gulp.dest( paths.dist + 'blueimp-load-image'));

    gulp.src(paths.vendors + 'i18next/release/i18next.amd-1.6.3.js')
        .pipe(gulp.dest( paths.dist + 'i18next'));

    gulp.src(paths.vendors + 'bootstrap-multiselect/dist/**/*')
        .pipe(gulp.dest( paths.dist + 'bootstrap-multiselect'));

    gulp.src([paths.vendors + 'jquery-galleria/src/galleria.js', paths.vendors + 'jquery-galleria/src/themes/classic/*'])
        .pipe(gulp.dest( paths.dist + 'jquery-galleria'));

    gulp.src(paths.vendors + 'zxcvbn/dist/zxcvbn.js')
        .pipe(gulp.dest( paths.dist + 'zxcvbn'));

    gulp.start('copy-dev-vendors');
});

gulp.task('bootstrap-js', function () {
    var btSource = [
        paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-transition.js',
        paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-alert.js',
        paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-modal.js',
        paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-dropdown.js',
        paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-scrollspy.js',
        paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-tab.js',
        paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-tooltip.js',
        paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-popover.js',
        paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-button.js',
        paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-collapse.js',
        paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-carousel.js',
        paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-typeahead.js',
        paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-affix.js'];

    gulp.src(btSource)
        .pipe(concat('bootstrap.js'))
        .pipe(gulp.dest( paths.dist + 'bootstrap/js'))
        .pipe(uglify())
        .pipe(rename({ extname: '.min.js' }))
        .pipe(gulp.dest( paths.dist + 'bootstrap/js'))
});
gulp.task('bootstrap-assets', function () {
    gulp.src([paths.vendors + 'bootstrap-sass/vendor/assets/images/**/*'])
        .pipe(gulp.dest( paths.dist + 'bootstrap/img'));

});
gulp.task('build-bootstrap', ['bootstrap-assets', 'bootstrap-js'], function () {
    gulp.src(paths.vendors + 'bootstrap-sass/vendor/assets/stylesheets/bootstrap.scss')
        .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(gulp.dest( paths.dist + 'bootstrap/css/'))
        .pipe(cssmin())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest( paths.dist + 'bootstrap/css'));
    gulp.src(paths.vendors + 'bootstrap-sass/vendor/assets/stylesheets/bootstrap-responsive.scss')
        .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(gulp.dest( paths.dist + 'bootstrap/css/'))
        .pipe(cssmin())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest( paths.dist + 'bootstrap/css'));
});

gulp.task('install-dependencies', function(done){
    return gulp.src(['./bower.json'])
        .pipe(install());
});

gulp.task('copy-dependencies', ['install-dependencies'], function(){
    gulp.start('copy-vendors');
    gulp.start('build-bootstrap');
});

gulp.task('install-assets', ['clean:vendor'], function(){
    gulp.start('copy-dependencies');
});

// js fixtures should be present (./bin/developer phraseanet:generate-js-fixtures)
gulp.task('test', ['qunit','mocha_phantomjs']);
