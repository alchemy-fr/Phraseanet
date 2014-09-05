require.config({
    baseUrl: "../../scripts",
    paths: {
        "chai"    : "../assets/chai/chai",
        "fixtures": "../assets/js-fixtures/fixtures",
        "jquery": "../assets/jquery/jquery",
        "backbone": "../assets/backbone-amd/backbone",
        "i18n": "../assets/i18next/i18next.amd-1.6.3",
        "bootstrap": "../assets/bootstrap/js/bootstrap.min",
        "sinonchai": "../assets/sinon-chai/sinon-chai",
        "squire": "../assets/squire/Squire",
        "jquery.ui": "../assets/jquery.ui/jquery-ui",
        "underscore": "../assets/underscore-amd/underscore",
        "jquery.ui.widget": "../assets/jquery-file-upload/jquery.ui.widget",
        "jquery.cookie": "../assets/jquery.cookie/jquery.cookie",
        "jquery.treeview": "../assets/jquery.treeview/jquery.treeview",
        "jquery.tooltip": "../include/jquery.tooltip",
        "blueimp.loadimage" : "../assets/blueimp-load-image/load-image",
        "jfu.iframe-transport": "../assets/jquery-file-upload/jquery.iframe-transport",
        "jfu.fileupload": "../assets/jquery-file-upload/jquery.fileupload"
    },
    shim: {
        "jquery.ui": {
            deps: ["jquery"]
        },
        "jquery.treeview": {
            deps: ['jquery', 'jquery.cookie'],
            exports: '$.fn.treeview'
        },
        bootstrap:{
            deps: ['jquery']
        },
        "jquery.cookie": {
            deps: ["jquery"],
            exports: '$.fn.cookie'
        },
        "jquery.tooltip": {
            deps: ["jquery"],
            exports: '$.fn.tooltip'
        },
        "jquery.ui": {
            deps: ["jquery"]
        },
        "jquery.ui.widget": {
            deps: ["jquery"]
        },
        "jfu.fileupload": {
            deps: ["jquery.ui.widget"]
        }
    }
});