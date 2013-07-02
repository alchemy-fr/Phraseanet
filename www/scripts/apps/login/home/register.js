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
    "common/forms/views/formType/passwordSetter",
    "jqueryui",
    "jquery.geocompleter"
], function($, i18n, Common, RegisterForm) {
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
            Common.initialize();

            var rules =  [{
                name: "email",
                rules: "required",
                message: i18n.t("validation_blank")
            },{
                name: "email",
                rules: "valid_email",
                message: i18n.t("validation_email")
            },{
                name: "password[password]",
                rules: "required",
                message: i18n.t("validation_blank")
            },{
                name: "password[password]",
                rules: "min_length[5]",
                message: i18n.t("validation_length_min", {
                    postProcess: "sprintf",
                    sprintf: ["5"]
                })
            },{
                name: "password[confirm]",
                rules: "matches[password[password]]",
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

            var $form = $("form[name=registerForm]");

            new RegisterForm({
                el : $form,
                rules: rules
            });

            var geocompleter = $("#geonameid").geocompleter({
                "server": $form.data("geonames-server-adress"),
                "limit": 40
            });

            // Positioning menu below input
            geocompleter.geocompleter("autocompleter", "option", "position", {
                "of": geocompleter.closest(".input-table"),
                "my": "left top",
                "at": "left bottom"
            });

            // On focus add select-state
            geocompleter.geocompleter("autocompleter", "on", "autocompletefocus", function(event, ui) {
                $("li", $(event.originalEvent.target)).closest("li").removeClass("selected");
                $("a.ui-state-active, a.ui-state-hover, a.ui-state-focus", $(event.originalEvent.target)).closest("li").addClass("selected");
            });

            // On search request add loading-state
            geocompleter.geocompleter("autocompleter", "on", "autocompletesearch", function(event, ui) {
                $(this).addClass('input-loading');
                $(this).removeClass('input-error');
            });

            // On open menu calculate max-width
            geocompleter.geocompleter("autocompleter", "on", "autocompleteopen", function(event, ui) {
                $(this).autocomplete("widget").css("min-width", geocompleter.closest(".input-table").outerWidth());
            });

            // On response remove loading-state
            geocompleter.geocompleter("autocompleter", "on", "autocompleteresponse", function(event, ui) {
                $(this).removeClass('input-loading');
            });

            // On close menu remove loading-state
            geocompleter.geocompleter("autocompleter", "on", "autocompleteclose", function(event, ui) {
                $(this).removeClass('input-loading');
            });

            // On request error add error-state
            geocompleter.geocompleter("autocompleter", "on", "geotocompleter.request.error", function(jqXhr, status, error) {
                $(this).removeClass('input-loading');
                $(this).addClass('input-error');
            });
        });
    });
});
