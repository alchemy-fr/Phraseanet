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
        jquery: "../assets/jquery/jquery",
        "jquery.ui": "../assets/jquery.ui/jquery-ui",
        underscore: "../assets/underscore-amd/underscore",
        backbone: "../assets/backbone-amd/backbone",
        "jquery.ui.widget": "../assets/jquery-file-upload/jquery.ui.widget",
        "jquery.cookie": "../assets/jquery.cookie/jquery.cookie",
        "jquery.treeview": "../assets/jquery.treeview/jquery.treeview",
        "jquery.tooltip": "../assets/jquery.tooltip/jquery.tooltip",
        "blueimp.loadimage" : "../assets/blueimp-load-image/load-image",
        "jfu.iframe-transport": "../assets/jquery-file-upload/jquery.iframe-transport",
        "jfu.fileupload": "../assets/jquery-file-upload/jquery.fileupload",
        i18n: "../assets/i18next/i18next.amd-1.6.3",
        bootstrap: "../assets/bootstrap/js/bootstrap.min"
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