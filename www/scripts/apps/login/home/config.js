/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
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
        i18n: "../assets/i18next/i18next.amd-1.6.3",
        bootstrap: "../assets/bootstrap/js/bootstrap.min",
        multiselect: "../assets/bootstrap-multiselect/js/bootstrap-multiselect",
        "jquery.geonames": "../assets/geonames-server-jquery-plugin/jquery.geonames"
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
        "common/geonames": {
            deps: ["jquery.geonames"]
        },
        multiselect: {
            deps: ["jquery", "bootstrap"]
        }
    }
});
