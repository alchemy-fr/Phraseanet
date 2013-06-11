/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// configure AMD loading
require.config({
    baseUrl: "/scripts",
    paths: {
        jquery: "../include/jslibs/jquery-1.7.1",
        jqueryui: "../include/jslibs/jquery-ui-1.8.17/js/jquery-ui-1.8.17.custom.min",
        underscore: "../assets/underscore-amd/underscore",
        backbone: "../assets/backbone-amd/backbone",
        i18n: "../assets/i18next/release/i18next.amd-1.6.2.min",
        bootstrap: "../skins/html5/bootstrap/js/bootstrap.min",
        multiselect: "../assets/bootstrap-multiselect/js/bootstrap-multiselect"
    },
    shim: {
        bootstrap : ["jquery"],
        jqueryui: {
            deps: ["jquery"]
        },
        multiselect: {
            deps: ["jquery", "bootstrap"]
        }
    }
});