module.exports = function(grunt) {
    grunt.initConfig({
        mocha_phantomjs: {
            all: ['www/scripts/tests/index.html']
        },
        qunit: {
            all: ['www/include/js/tests/*.html']
        },
        clean: {
            "assets": ["assets", "www/assets"],
        },
        bower: {
            install: {
                options: {
                    "copy": false
                }
            }
        },
        bower_postinst: {
            dist: {
                options: {
                    components: {
                        "jquery.ui": ["npm", {"grunt": "build"}],
                        "bootstrap": ["npm", {"make": "bootstrap"}]
                    }
                }
            }
        },
        copy: {
            "backbone": {
                "expand": true,
                "src": [
                    "assets/backbone-amd/LICENSE",
                    "assets/backbone-amd/backbone.js"
                ],
                "dest": "www/assets/backbone-amd/",
                "flatten": true
            },
            "blueimp": {
                "expand": true,
                "src": "js/load-image.js",
                "dest": "www/assets/blueimp-load-image/",
                "cwd": "assets/blueimp-load-image",
                "flatten": true
            },
            "bootstrap": {
                "expand": true,
                "cwd": "assets/bootstrap",
                "src": [
                    "bootstrap/css/*",
                    "bootstrap/js/*",
                    "bootstrap/img/*",
                    "LICENSE"
                ],
                "rename": function(dest, src) {
                    return dest + src.replace("bootstrap", "");
                },
                "dest": "www/assets/bootstrap/"
            },
            "bootstrap-multiselect": {
                "expand": true,
                "cwd": "assets/bootstrap-multiselect",
                "src": [
                    "css/bootstrap-multiselect.css",
                    "js/bootstrap-multiselect.js",
                ],
                "dest": "www/assets/bootstrap-multiselect/"
            },
            "chai": {
                "expand": true,
                "src": "assets/chai/chai.js",
                "dest": "www/assets/chai/",
                "flatten": true
            },
            "font-awesome": {
                "expand": true,
                "cwd": "assets/font-awesome",
                "src": ["css/*", "font/*"],
                "dest": "www/assets/font-awesome/"
            },
            "geonames-server-jquery-plugin": {
                "expand": true,
                "flatten": true,
                "src": [
                    "assets/geonames-server-jquery-plugin/LICENSE",
                    "assets/geonames-server-jquery-plugin/jquery.geonames.js"
                ],
                "dest": "www/assets/geonames-server-jquery-plugin"
            }
            ,
            "humane-js": {
                "expand": true,
                "src": ["humane.js", "themes/libnotify.css"],
                "dest": "www/assets/humane-js/",
                "cwd": "assets/humane-js/"
            },
            "i18next": {
                "expand": true,
                "src": "assets/i18next/release/i18next.amd-1.6.3.js",
                "dest": "www/assets/i18next/",
                "flatten": true
            },
            "jquery": {
                "expand": true,
                "src": "assets/jquery/jquery.js",
                "dest": "www/assets/jquery/",
                "flatten": true
            },
            "jquery-file-upload": {
                "expand": true,
                "src": [
                    "assets/jquery-file-upload/js/jquery.fileupload.js",
                    "assets/jquery-file-upload/js/jquery.iframe-transport.js",
                    "assets/jquery-file-upload/css/jquery.fileupload-ui.css"
                ],
                "dest": "www/assets/jquery-file-upload/",
                "flatten": true
            },
            "jquery-ui": {
                "expand": true,
                "cwd": "assets/jquery.ui",
                "src": [
                    "dist/i18n/*",
                    "dist/images/*",
                    "themes/base/*",
                    "themes/base/images/*",
                    "dist/jquery-ui.css",
                    "dist/jquery-ui.js"
                ],
                "rename": function(dest, src) {
                    return dest + src.replace("dist", "");
                },
                "dest": "www/assets/jquery.ui/"
            },
            "js-fixtures": {
                "expand": true,
                "src": [
                    "assets/js-fixtures/LICENSE",
                    "assets/js-fixtures/fixtures.js"
                ],
                "dest": "www/assets/js-fixtures/",
                "flatten": true
            },
            "json3": {
                "expand": true,
                "src": [
                    "assets/json3/LICENSE",
                    "assets/json3/lib/json3.js"
                ],
                "dest": "www/assets/json3/",
                "flatten": true
            },
            "mocha": {
                "expand": true,
                "src": [
                    "assets/mocha/LICENSE",
                    "assets/mocha/mocha.js",
                    "assets/mocha/mocha.css"
                ],
                "dest": "www/assets/mocha/",
                "flatten": true
            },
            "modernizr": {
                "expand": true,
                "src": "assets/modernizr/modernizr.js",
                "dest": "www/assets/modernizr/",
                "flatten": true
            },
            "normalize": {
                "expand": true,
                "src": [
                    "assets/normalize-css/normalize.css",
                    "assets/normalize-css/LICENSE.md"
                ],
                "dest": "www/assets/normalize-css/",
                "flatten": true
            },
            "qunit": {
                "expand": true,
                "src": [
                    "qunit/qunit.css",
                    "qunit/qunit.js",
                    "addons/phantomjs/*"
                ],
                "dest": "www/assets/qunit/",
                "cwd": "assets/qunit/",
                "rename": function(dest, src) {
                    return dest + src.replace("qunit", "");
                },
            },
            "requirejs": {
                "expand": true,
                "src": [
                    "assets/requirejs/LICENSE",
                    "assets/requirejs/require.js"
                ],
                "dest": "www/assets/requirejs/",
                "flatten": true
            },
            "underscore": {
                "expand": true,
                "src": [
                    "assets/underscore-amd/LICENSE",
                    "assets/underscore-amd/underscore.js"
                ],
                "dest": "www/assets/underscore-amd/",
                "flatten": true
            },
            "zxcvbn": {
                "expand": true,
                "src": [
                    "assets/zxcvbn/LICENSE.txt",
                    "assets/zxcvbn/zxcvbn-async.js"
                ],
                "dest": "www/assets/zxcvbn",
                "flatten": true
            }
        },
        less: {
            login: {
                options: {
                    paths: ["www/skins/login/less"],
                },
                files: {
                    "www/assets/build/login.css": "www/skins/login/less/login.less"
                }
            },
            account: {
                options: {
                    paths: ["www/skins/account"],
                },
                files: {
                    "www/assets/build/account.css": "www/skins/account/account.less"
                }
            },
        }
    });

    grunt.loadNpmTasks("grunt-contrib");
    grunt.loadNpmTasks("grunt-bower-task");
    grunt.loadNpmTasks("grunt-bower-postinst");
    grunt.loadNpmTasks('grunt-mocha-phantomjs');

    grunt.registerTask("build-assets", ["clean:assets", "bower", "bower_postinst", "copy", "less"]);
    grunt.registerTask('test', ["qunit", "mocha_phantomjs"]);
};
