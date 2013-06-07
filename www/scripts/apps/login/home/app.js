/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "jquery",
    "underscore",
    "backbone",
    "apps/login/home/views/loginForm"
], function($, _, Backbone, LoginForm) {
    var initialize = function() {
        var loginFormView = new LoginForm({
            el : $("form[name=loginForm]"),
            rules: [{
                name: "login",
                rules: "required",
                message: "This field is requerid"
            },{
                name: "login",
                rules: "valid_email",
                message: "This field must be a valid email"
            },{
                name: "password",
                rules: "required",
                message: "This field is requerid"
            }]
        });
    };

    return {
        initialize: initialize
    };
});
