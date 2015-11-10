


// TODO: bower, tmp-assets
// TODO: asset, www/assets
var gulp = require('gulp');
var rename = require('gulp-rename');
var clean = require('gulp-clean');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var cssmin = require('gulp-cssmin');
var install = require('gulp-install');
var sass = require('gulp-sass');
var del = require('del');
var config = require('./config.js');

// gulp.task('clean:vendors', function(done){
//     return del([config.paths.distVendors + '/**/*'], done);
// });
//
//
// var lib = require('bower-files')({
//     overrides: {
//         'backbone-amd': {
//             main: ['./backbone.js', 'LICENSE']
//         },
//         'font-awesome': {
//             main: ['css/*', 'font/*']
//         },
//         'humane-js': {
//             main: ['humane.js', 'themes/libnotify.css']
//         },
//         'jquery': {
//             main: 'jquery.js'
//         },
//         'jquery.treeview': {
//             main: ['images/*',
//                 'jquery.treeview*']
//         },
//         'jquery-mobile-bower': {
//             main: ['css/jquery.mobile-1.3.2.css', 'js/jquery.mobile-1.3.2.js']
//         },
//         'json2': {
//             main: [/* ignore, will be copied manually in groups */]
//         },
//         'modernizr': {
//             main: [/* ignore, will be copied manually in groups */]
//         },
//         'tinymce': {
//             main: ['plugins/**',
//                 'skins/**',
//                 'themes/**',
//                 '*.js',
//                 'changelog.txt',
//                 'license.txt']
//         },
//         'underscore-amd': {
//             main: [/* ignore, will be copied manually in groups */]
//         },
//         zxcvbn: {
//             main: [/* ignore, will be copied manually for legacy code compatibility */]
//         },
//         'jquery-galleria': {
//             main: [/* ignore, will be copied manually for legacy code compatibility */]
//         },
//         'bootstrap-multiselect': {
//             main: [/* ignore, will be copied manually for legacy code compatibility */]
//         },
//         'fancytree': {
//             main: [/* ignore, will be copied manually for legacy code compatibility */]
//         },
//         'jquery-ui': {
//             main: [/* ignore, will be copied manually for legacy code compatibility */]
//         },
//         'swfobject': {
//             main: [/* ignore, will be copied manually for legacy code compatibility */]
//         },
//         'blueimp-load-image': {
//             main: [/* ignore, will be copied manually for legacy code compatibility */]
//         },
//         'jquery-file-upload': {
//             main: [/* ignore, will be copied manually for legacy code compatibility */]
//         },
//         'i18next': {
//             main: [/* ignore, will be copied manually for legacy code compatibility */]
//         }
//     }
// });
//
// gulp.task('copy-dev-vendors', function(){
//     gulp.src(config.paths.vendors + 'chai/chai.js')
//         .pipe(gulp.dest( config.paths.distVendors + 'chai/'));
//
//     gulp.src(config.paths.vendors + 'js-fixtures/fixtures.js')
//         .pipe(gulp.dest( config.paths.distVendors + 'js-fixtures/'));
//
//     gulp.src([config.paths.vendors + 'mocha/mocha.js', config.paths.vendors + 'mocha/mocha.css'])
//         .pipe(gulp.dest( config.paths.distVendors + 'mocha/'));
//
//     gulp.src(config.paths.vendors + 'sinon-chai/lib/sinon-chai.js')
//         .pipe(gulp.dest( config.paths.distVendors + 'sinon-chai/'));
//
//     gulp.src(config.paths.vendors + 'sinonjs/sinon.js')
//         .pipe(gulp.dest( config.paths.distVendors + 'sinonjs/'));
//
//     gulp.src(config.paths.vendors + 'squire/src/Squire.js')
//         .pipe(gulp.dest( config.paths.distVendors + 'squire/'));
//
//     gulp.src([config.paths.vendors + 'qunit/qunit/qunit.js', config.paths.vendors + 'qunit/qunit/qunit.css'])
//         .pipe(gulp.dest( config.paths.distVendors + 'qunit/'));
//
//     gulp.src(config.paths.vendors + 'qunit/addons/phantomjs/*')
//         .pipe(gulp.dest( config.paths.distVendors + 'qunit/addons/phantomjs'));
// });
//
// gulp.task('copy-vendors-via-bower', function () {
//     var vendorConfig = {
//         'jquery-mobile-bower/css': {
//             dirname: 'jquery-mobile'
//         },
//         'jquery-mobile-bower/js': {
//             dirname: 'jquery-mobile'
//         }
//     };
//
//     return gulp.src(lib.ext().files, { base: config.paths.vendors })
//         .pipe(rename(function (path) {
//             if( vendorConfig[path.dirname] !== undefined ) {
//                 // console.log('reading path', path)
//                 var cuConf = vendorConfig[path.dirname];
//                 path.dirname = cuConf.dirname;
//             }
//             return path;
//         }))
//         .pipe(gulp.dest(config.paths.distVendors));
// });
//
// // copy additionnal assets from vendors to match old legacy assets:
// gulp.task('copy-vendors', ['copy-vendors-via-bower'],function () {
//     gulp.src([config.paths.vendors + 'fancytree/dist/skin-win8/**/*']) //, config.paths.vendors + 'fancytree/dist/jquery.fancytree-all.min
//         .pipe(gulp.dest( config.paths.distVendors + 'fancytree/dist/skin-win8'))
//
//     gulp.src(config.paths.vendors + 'jquery-ui/ui/jquery-ui.js')
//         .pipe(gulp.dest( config.paths.distVendors + 'jquery.ui/'));
//
//     gulp.src(config.paths.vendors + 'jquery-ui/themes/base/*.css')
//         .pipe(gulp.dest( config.paths.distVendors + 'jquery.ui/'));
//
//     gulp.src(config.paths.vendors + 'jquery-ui/ui/i18n/*')
//         .pipe(gulp.dest( config.paths.distVendors + 'jquery.ui/i18n'));
//
//     gulp.src(config.paths.vendors + 'jquery-ui/themes/base/images/*')
//         .pipe(gulp.dest( config.paths.distVendors + 'jquery.ui/images'));
//
//     gulp.src(config.paths.vendors + 'swfobject/swfobject/swfobject.js')
//         .pipe(gulp.dest( config.paths.distVendors + 'swfobject'));
//
//
//     gulp.src([config.paths.vendors + 'jquery-file-upload/js/*', config.paths.vendors + 'jquery-file-upload/js/vendor/*', config.paths.vendors + 'jquery-file-upload/css/*'])
//         .pipe(gulp.dest( config.paths.distVendors + 'jquery-file-upload'));
//
//     gulp.src(config.paths.vendors + 'blueimp-load-image/js/*.js')
//         .pipe(gulp.dest( config.paths.distVendors + 'blueimp-load-image'));
//
//     gulp.src(config.paths.vendors + 'i18next/release/i18next.amd-1.6.3.js')
//         .pipe(gulp.dest( config.paths.distVendors + 'i18next'));
//
//     gulp.src(config.paths.vendors + 'bootstrap-multiselect/dist/**/*')
//         .pipe(gulp.dest( config.paths.distVendors + 'bootstrap-multiselect'));
//
//     gulp.src([config.paths.vendors + 'jquery-galleria/src/galleria.js', config.paths.vendors + 'jquery-galleria/src/themes/classic/*'])
//         .pipe(gulp.dest( config.paths.distVendors + 'jquery-galleria'));
//
//     gulp.src(config.paths.vendors + 'zxcvbn/dist/zxcvbn.js')
//         .pipe(gulp.dest( config.paths.distVendors + 'zxcvbn'));
//
//     gulp.start('copy-dev-vendors');
// });

gulp.task('bootstrap-js', function () {
    var btSource = [
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-transition.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-alert.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-modal.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-dropdown.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-scrollspy.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-tab.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-tooltip.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-popover.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-button.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-collapse.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-carousel.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-typeahead.js',
        config.paths.vendors + 'bootstrap-sass/vendor/assets/javascripts/bootstrap-affix.js'];

    gulp.src(btSource)
        .pipe(concat('bootstrap.js'))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/js'))
        .pipe(uglify())
        .pipe(rename({ extname: '.min.js' }))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/js'))
});
gulp.task('bootstrap-assets', function () {
    gulp.src([config.paths.vendors + 'bootstrap-sass/vendor/assets/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/img'));

});
gulp.task('build-bootstrap', ['bootstrap-assets', 'bootstrap-js'], function () {
    gulp.src(config.paths.vendors + 'bootstrap-sass/vendor/assets/stylesheets/bootstrap.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/css/'))
        .pipe(cssmin())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/css'));
    gulp.src(config.paths.vendors + 'bootstrap-sass/vendor/assets/stylesheets/bootstrap-responsive.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/css/'))
        .pipe(cssmin())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest( config.paths.build + 'vendors/bootstrap/css'));
});
gulp.task('install-bower-dependencies', function(){
    return gulp.src(['./bower.json'])
        .pipe(install());
});

gulp.task('install-dependencies', ['install-bower-dependencies'], function(done){
    gulp.start('copy-vendors');
    gulp.start('build-bootstrap');
});

gulp.task('deploy-dependencies', ['install-dependencies'], function(){
    //gulp.start('copy-vendors');
    //gulp.start('build-bootstrap');
    gulp.start('build');
});


gulp.task('install-assets', ['clean:vendors'], function(){
    gulp.start('deploy-dependencies');
});

gulp.task('install', function(){
    gulp.start('build-bootstrap');
    gulp.start('build-css');
    gulp.start('build');
});
