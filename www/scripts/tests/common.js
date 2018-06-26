require.config({
    baseUrl: "../../scripts",
    paths: {
        "chai": "../../node_modules/chai/lib/chai",
        "fixtures": "../../node_modules/js-fixtures/fixtures",
        jquery: "../assets/vendors/jquery/jquery.min",
        underscore: "../assets/vendors/underscore/underscore.min",
        backbone: "../assets/vendors/backbone/backbone.min",
        i18n: "../assets/vendors/i18next/i18next.min",
        bootstrap: "../assets/vendors/bootstrap/js/bootstrap.min",
        "sinonchai": "../../node_modules/sinon-chai/lib/sinon-chai",
        "squire": "../../node_modules/squirejs/src/Squire",
        "jquery.ui": "../assets/vendors/jquery-ui/jquery-ui.min",
        "jquery.ui.widget": "../assets/vendors/jquery-file-upload/jquery.ui.widget.min",
        "jquery.cookie": "../assets/vendors/jquery.cookie/jquery.cookie.min",
        "jquery.treeview": "../assets/vendors/jquery-treeview/jquery.treeview",
        //"jquery.tooltip": "../include/jquery.tooltip",
        "blueimp.loadimage" : "../assets/vendors/blueimp-load-image/load-image",
        "jfu.iframe-transport": "../assets/vendors/jquery-file-upload/jquery.iframe-transport",
        "jfu.fileupload": "../assets/vendors/jquery-file-upload/jquery.fileupload",


        //"jquery.geonames": "../assets/vendors/jquery.geonames/jquery.geonames",





        //"jquery.tooltip": "../include/jquery.tooltip",




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
        /*"jquery.tooltip": {
            deps: ["jquery"],
            exports: '$.fn.tooltip'
        },*/
        "jquery.ui.widget": {
            deps: ["jquery"]
        },
        "jfu.fileupload": {
            deps: ["jquery.ui.widget"]
        }
    }
});