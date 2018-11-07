/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require([
    "jquery",
    "i18n",
    "apps/login/home/common",
    "common/forms/views/form"
], function ($, i18n, Common, RenewEmail) {
    i18n.init({
        resGetPath: Common.languagePath,
        useLocalStorage: true
    }, function () {
        Common.initialize();

        new RenewEmail({
            el: $("form[name=changeEmail]"),
            errorTemplate: "#field_errors_block",
            onRenderError: function (name, $el) {
                $el.closest(".control-group").addClass("error");
            },
            rules: [
                {
                    name: "form_password",
                    rules: "required",
                    message: i18n.t("validation_blank")
                },
                {
                    name: "form_email",
                    rules: "required",
                    message: i18n.t("validation_blank")
                },
                {
                    name: "form_email",
                    rules: "email",
                    message: i18n.t("validation_email")
                },
                {
                    name: "form_email_confirm",
                    rules: "matches[form_email]",
                    message: i18n.t("email_match")
                }
            ]
        });
    });
});
