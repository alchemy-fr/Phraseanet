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
        jqueryui: "../include/jslibs/jquery-ui-1.10.3",
        underscore: "../assets/underscore-amd/underscore",
        backbone: "../assets/backbone-amd/backbone",
        i18n: "../assets/i18next/release/i18next.amd-1.6.2.min",
        bootstrap: "../skins/build/bootstrap/js/bootstrap.min"
    },
    shim: {
        bootstrap: ["jquery"],
        jqueryui: {
            deps: [ "jquery" ]
        }
    }
});

// launch application
require(["apps/admin/fields/app"], function (App) {
    App.initialize();
});
