/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "jquery",
    "underscore",
    "i18n",
    "backbone",
    "bootstrap",
    "common/forms/views/form",
    "common/forms/views/formType/zxcvbn"
], function ($, _, i18n, Backbone, bootstrap, FormView, zxcvbn) {
    var PasswordSetterForm = FormView.extend({
        events: function () {
            return _.extend({}, FormView.prototype.events, {
                'keyup input[type=password]': 'onPasswordKeyup'
            });
        },
        onPasswordKeyup: function (event) {
            var input = $(event.target);
            var password = input.val();
            var inputView = this.inputViews[input.attr("name")];
            var bg = $(".password_strength_bg", inputView.$el);
            var label = $(".password_strength_label", inputView.$el);
            var desc = $(".password_strength_desc", inputView.$el);
            var css = {
                "width": "0%",
                "background-color": "rgb(39, 39, 30)"
            };
            var result = "";

            if (password.length > 0) {
                var passMeter = zxcvbn(input.val());

                switch (passMeter.score) {
                    case 0:
                    case 1:
                        css = {
                            "width": "25%",
                            "background-color": "rgb(200, 24, 24)"
                        };
                        result = i18n.t("weak");
                        break;
                    case 2:
                        css = {
                            "width": "50%",
                            "background-color": "rgb(255, 172, 29)"
                        };
                        result = i18n.t("ordinary");
                        break;
                    case 3:
                        css = {
                            "width": "75%",
                            "background-color": "rgb(166, 192, 96)"
                        };
                        result = i18n.t("good");
                        break;
                    case 4:
                        css = {
                            "width": "100%",
                            "background-color": "rgb(39, 179, 15)"
                        };
                        result = i18n.t("great");
                        break;
                }
            }

            bg.css(css);
            label.css({"color": css["background-color"]});
            desc.css({"color": css["background-color"]}).html(result);
        }
    });

    return PasswordSetterForm;
});
