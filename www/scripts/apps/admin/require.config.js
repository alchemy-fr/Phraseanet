/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// configure AMD loading
require.config({
    baseUrl: "/scripts",
    paths: {
        jquery: "../assets/vendors/jquery/jquery.min",
        "jquery.geonames": "../assets/vendors/jquery.geonames/jquery.geonames",
        "jquery.ui": "../assets/vendors/jquery-ui/jquery-ui.min",
        underscore: "../assets/vendors/underscore/underscore.min",
        backbone: "../assets/vendors/backbone/backbone.min",
        zxcvbn: "../assets/vendors/zxcvbn/zxcvbn.min",
        "jquery.ui.widget": "../assets/vendors/jquery-file-upload/jquery.ui.widget.min",
        "jquery.cookie": "../assets/vendors/jquery.cookie/jquery.cookie.min",
        "jquery.treeview": "../assets/vendors/jquery-treeview/jquery.treeview",
        //"jquery.tooltip": "../include/jquery.tooltip",
        "blueimp.loadimage" : "../assets/vendors/blueimp-load-image/load-image",
        "jfu.iframe-transport": "../assets/vendors/jquery-file-upload/jquery.iframe-transport",
        "jfu.fileupload": "../assets/vendors/jquery-file-upload/jquery.fileupload",
        i18n: "../assets/vendors/i18next/i18next.min",
        bootstrap: "../assets/vendors/bootstrap/js/bootstrap.min",
    },
    shim: {
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
        "jquery.geonames": {
            deps: ["jquery"],
            exports: '$.fn.geocompleter'
        },
        "jquery.tooltip": {
            deps: ["jquery"],
            exports: '$.fn.geocompleter'
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
