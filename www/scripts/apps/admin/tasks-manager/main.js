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
        underscore: "../assets/underscore-amd/underscore",
        backbone: "../assets/backbone-amd/backbone"
    }
});

// launch application
require(["apps/admin/tasks-manager/app"], function (App) {
    App.initialize();
});
