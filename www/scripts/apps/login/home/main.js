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
        bootstrap: "../skins/html5/bootstrap/js/bootstrap.min"
    },
    shim: {
        bootstrap : ["jquery"],
        jqueryui: {
            deps: ["jquery"]
        }
    }
});

// launch application
require(["apps/login/home/app"], function(App) {
    App.initialize();
});

// close alerts
$(document).ready(function() {
    $(document).on("click", ".alert .alert-block-close a", function(e){
        e.preventDefault();
        $(this).closest('.alert').alert('close');
        return false;
    });

    $("select[multiple='multiple']").multiselect({
        buttonWidth : "100%",
        buttonClass: 'btn btn-inverse'
    });
});
