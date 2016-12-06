/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// configure AMD loading
require.config({
    baseUrl: "/scripts",
    paths: {
        jquery: "../assets/vendors/jquery/jquery.min",
        "jquery.ui": "../assets/vendors/jquery-ui/jquery-ui.min",
        underscore: "../assets/vendors/underscore/underscore.min",
        "zxcvbn": "../assets/vendors/zxcvbn/zxcvbn.min",
        backbone: "../assets/vendors/backbone/backbone.min",
        i18n: "../assets/vendors/i18next/i18next.min",
        bootstrap: "../assets/vendors/bootstrap/js/bootstrap.min",
        multiselect: "../assets/vendors/bootstrap-multiselect/bootstrap-multiselect",
        "jquery.geonames": "../assets/vendors/jquery.geonames/jquery.geonames"
    },
    shim: {
        bootstrap: ["jquery"],
        "jquery.ui": {
            deps: ["jquery"]
        },
        "jquery.geonames": {
            deps: ["jquery", "jquery.ui"],
            exports: "$.fn.geocompleter"
        },
        "zxcvbn": {
            deps: ["jquery"]
        },
        "common/geonames": {
            deps: ["jquery.geonames"]
        },
        multiselect: {
            deps: ["jquery", "bootstrap"]
        }
    }
});
