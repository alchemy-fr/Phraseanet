module.exports = function(grunt) {
    grunt.initConfig({
        "pkg": grunt.file.readJSON("package.json"),
        "path": {
            "bower": "tmp-assets",
            "asset": "www/assets"
        },
        mocha_phantomjs: {
            options: {
                'reporter': 'dot'
            },
            all: ['www/scripts/tests/index.html']
        },
        qunit: {
            all: ['www/include/js/tests/*.html']
        },
        clean: {
            "assets": ["<%= path.bower %>", "<%= path.asset %>"]
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
                        "jquery-mobile": ["npm", {"grunt": "dist"}],
                        "tinymce": ["npm", "jake"],
                        "bootstrap": ["npm", {"make": "bootstrap"}]
                    }
                }
            }
        },
        copy: {
            "backbone": {
                "expand": true,
                "src": [
                    "<%= path.bower %>/backbone-amd/LICENSE",
                    "<%= path.bower %>/backbone-amd/backbone.js"
                ],
                "dest": "<%= path.asset %>/backbone-amd/",
                "flatten": true
            },
            "blueimp": {
                "expand": true,
                "src": "js/load-image.js",
                "dest": "<%= path.asset %>/blueimp-load-image/",
                "cwd": "<%= path.bower %>/blueimp-load-image",
                "flatten": true
            },
            "bootstrap": {
                "expand": true,
                "cwd": "<%= path.bower %>/bootstrap",
                "src": [
                    "bootstrap/css/*",
                    "bootstrap/js/*",
                    "bootstrap/img/*",
                    "less/*.less",
                    "LICENSE"
                ],
                "rename": function(dest, src) {
                    return dest + src.replace("bootstrap", "");
                },
                "dest": "<%= path.asset %>/bootstrap/"
            },
            "bootstrap-multiselect": {
                "expand": true,
                "cwd": "<%= path.bower %>/bootstrap-multiselect",
                "src": [
                    "css/bootstrap-multiselect.css",
                    "js/bootstrap-multiselect.js"
                ],
                "dest": "<%= path.asset %>/bootstrap-multiselect/"
            },
            "chai": {
                "expand": true,
                "src": "<%= path.bower %>/chai/chai.js",
                "dest": "<%= path.asset %>/chai/",
                "flatten": true
            },
            "font-awesome": {
                "expand": true,
                "cwd": "<%= path.bower %>/font-awesome",
                "src": ["css/*", "font/*"],
                "dest": "<%= path.asset %>/font-awesome/"
            },
            "geonames-server-jquery-plugin": {
                "expand": true,
                "flatten": true,
                "src": [
                    "<%= path.bower %>/geonames-server-jquery-plugin/LICENSE",
                    "<%= path.bower %>/geonames-server-jquery-plugin/jquery.geonames.js"
                ],
                "dest": "<%= path.asset %>/geonames-server-jquery-plugin"
            }
            ,
            "humane-js": {
                "expand": true,
                "src": ["humane.js", "themes/libnotify.css"],
                "dest": "<%= path.asset %>/humane-js/",
                "cwd": "<%= path.bower %>/humane-js/"
            },
            "i18next": {
                "expand": true,
                "src": "<%= path.bower %>/i18next/release/i18next.amd-1.6.3.js",
                "dest": "<%= path.asset %>/i18next/",
                "flatten": true
            },
            "jquery": {
                "expand": true,
                "src": "<%= path.bower %>/jquery/jquery.js",
                "dest": "<%= path.asset %>/jquery/",
                "flatten": true
            },
            "jquery-file-upload": {
                "expand": true,
                "src": [
                    "<%= path.bower %>/jquery-file-upload/js/jquery.fileupload.js",
                    "<%= path.bower %>/jquery-file-upload/js/jquery.iframe-transport.js",
                    "<%= path.bower %>/jquery-file-upload/css/jquery.fileupload-ui.css"
                ],
                "dest": "<%= path.asset %>/jquery-file-upload/",
                "flatten": true
            },
            "jquery-mobile": {
                "expand": true,
                "cwd": "<%= path.bower %>/jquery-mobile/dist",
                "src": [
                    "images/*",
                    "jquery.mobile.css",
                    "jquery.mobile.js"
                ],
                "dest": "<%= path.asset %>/jquery-mobile/"
            },
            "jquery.cookie": {
                "expand": true,
                "cwd": "<%= path.bower %>/jquery.cookie",
                "src": [
                    "jquery.cookie.js"
                ],
                "dest": "<%= path.asset %>/jquery.cookie/"
            },
            "jquery-ui": {
                "expand": true,
                "cwd": "<%= path.bower %>/jquery.ui",
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
                "dest": "<%= path.asset %>/jquery.ui/"
            },
            "js-fixtures": {
                "expand": true,
                "src": [
                    "<%= path.bower %>/js-fixtures/LICENSE",
                    "<%= path.bower %>/js-fixtures/fixtures.js"
                ],
                "dest": "<%= path.asset %>/js-fixtures/",
                "flatten": true
            },
            "json2": {
                "expand": true,
                "src": "<%= path.bower %>/json2/json2.js",
                "dest": "<%= path.asset %>/json2/",
                "flatten": true
            },
            "mocha": {
                "expand": true,
                "src": [
                    "<%= path.bower %>/mocha/LICENSE",
                    "<%= path.bower %>/mocha/mocha.js",
                    "<%= path.bower %>/mocha/mocha.css"
                ],
                "dest": "<%= path.asset %>/mocha/",
                "flatten": true
            },
            "modernizr": {
                "expand": true,
                "src": "<%= path.bower %>/modernizr/modernizr.js",
                "dest": "<%= path.asset %>/modernizr/",
                "flatten": true
            },
            "normalize": {
                "expand": true,
                "src": [
                    "<%= path.bower %>/normalize-css/normalize.css",
                    "<%= path.bower %>/normalize-css/LICENSE.md"
                ],
                "dest": "<%= path.asset %>/normalize-css/",
                "flatten": true
            },
            "qunit": {
                "expand": true,
                "src": [
                    "qunit/qunit.css",
                    "qunit/qunit.js",
                    "addons/phantomjs/*"
                ],
                "dest": "<%= path.asset %>/qunit/",
                "cwd": "<%= path.bower %>/qunit/",
                "rename": function(dest, src) {
                    return dest + src.replace("qunit", "");
                }
            },
            "requirejs": {
                "expand": true,
                "src": [
                    "<%= path.bower %>/requirejs/LICENSE",
                    "<%= path.bower %>/requirejs/require.js"
                ],
                "dest": "<%= path.asset %>/requirejs/",
                "flatten": true
            },
            "swfobject": {
                "expand": true,
                "src": "<%= path.bower %>/swfobject/swfobject/swfobject.js",
                "dest": "<%= path.asset %>/swfobject",
                "flatten": true
            },
            "tinymce": {
                "expand": true,
                "cwd": "<%= path.bower %>/tinymce/js/tinymce",
                "src": [
                    "plugins/**",
                    "skins/**",
                    "themes/**",
                    "tinymce.js",
                    "license.txt"
                ],
                "dest": "<%= path.asset %>/tinymce"
            },
            "underscore": {
                "expand": true,
                "src": [
                    "<%= path.bower %>/underscore-amd/LICENSE",
                    "<%= path.bower %>/underscore-amd/underscore.js"
                ],
                "dest": "<%= path.asset %>/underscore-amd/",
                "flatten": true
            },
            "zxcvbn": {
                "expand": true,
                "src": [
                    "<%= path.bower %>/zxcvbn/LICENSE.txt",
                    "<%= path.bower %>/zxcvbn/zxcvbn-async.js"
                ],
                "dest": "<%= path.asset %>/zxcvbn",
                "flatten": true
            }
        },
        csslint: {
            options: {
                // Possible Errors
                "box-model": false,
                "duplicate-properties": false,
                "empty-rules": false,
                "errors": false,
                "known-properties": false,
                "display-property-grouping": false,
                "non-link-hover": false,
                // Compatibility
                "adjoining-classes": false,
                "box-sizing": false,
                "compatible-vendor-prefixes": false,
                "gradients": false,
                "text-indent": false,
                "fallback-colors": false,
                "vendor-prefix": false,
                "star-property-hack": false,
                "underscore-property-hack": false,
                "bulletproof-font-face": false,
                // Performance
                "font-faces": false,
                "regex-selectors": false,
                "unqualified-attributes": false,
                "universal-selector": false,
                "zero-units": false,
                "overqualified-elements": false,
                "duplicate-background-images": false,
                "import": false,
                // Maintainability & Duplication
                "important": false,
                "floats": false,
                "font-sizes": false,
                "ids": false,
                // Accessibility
                "outline-none": false,
                // OOCSS
                "qualified-headings": false,
                "unique-headings": false,
                // Others
                "shorthand": false
            },
            all: {
                src: ['www/skins/**/*.css']
            }
        }
    });

    grunt.loadNpmTasks("grunt-contrib");
    grunt.loadNpmTasks("grunt-bower-task");
    grunt.loadNpmTasks("grunt-bower-postinst");
    grunt.loadNpmTasks('grunt-mocha-phantomjs');

    grunt.registerTask("install-assets", ["clean:assets", "bower", "bower_postinst", "copy"]);
    grunt.registerTask('test', ["qunit", "mocha_phantomjs"]);
};
