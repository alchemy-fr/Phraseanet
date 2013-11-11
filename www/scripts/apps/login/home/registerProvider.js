/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// launch application
require([
    "jquery",
    "i18n",
    "apps/login/home/common",
    "common/forms/views/form"
], function($, i18n, Common, RegisterForm) {
    Common.initialize();

    var fieldsConfiguration = [];

    $.when.apply($, [
            $.ajax({
                url: '/login/registration-fields/',
                success: function(config) {
                    fieldsConfiguration = config;
                }
            })
        ]).done(function(){
            i18n.init({
                resGetPath: Common.languagePath,
                useLocalStorage: true
            }, function() {
                var rules =  [{
                    name: "email",
                    rules: "required",
                    message: i18n.t("validation_blank")
                },{
                    name: "email",
                    rules: "valid_email",
                    message: i18n.t("validation_email")
                },{
                    name: "password",
                    rules: "required",
                    message: i18n.t("validation_blank")
                },{
                    name: "password",
                    rules: "min_length[5]",
                    message: i18n.t("validation_length_min", {
                        postProcess: "sprintf",
                        sprintf: ["5"]
                    })
                },{
                    name: "passwordConfirm",
                    rules: "matches[password]",
                    message: i18n.t("password_match")
                },{
                    name: "accept-tou",
                    rules: "required",
                    message: i18n.t("accept_tou"),
                    type: "checkbox"
                },{
                    name: "collections[]",
                    rules: "min_length[1]",
                    message: i18n.t("validation_choice_min", {
                        postProcess: "sprintf",
                        sprintf: ["1"]
                    }),
                    type: "multiple"
                }];

                _.each(fieldsConfiguration, function(field) {
                    if (field.required) {
                        var rule = {
                            "name": field.name,
                            "rules": "required",
                            "message": i18n.t("validation_blank")
                        };

                        rules.push(rule);
                    }
                });

                new RegisterForm({
                    el : $("form[name=registerForm]"),
                    rules: rules
                });
            });
        });
});
