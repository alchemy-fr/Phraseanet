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
    "common/forms/views/formType/passwordSetter"
], function ($, i18n, Common, RenewPassword) {
    i18n.init({
        resGetPath: Common.languagePath,
        useLocalStorage: true
    }, function () {
        Common.initialize();

        new RenewPassword({
            el: $("form[name=passwordChangeForm]"),
            rules: [
                {
                    name: "oldPassword",
                    rules: "required",
                    message: i18n.t("validation_blank")
                },
                {
                    name: "password[password]",
                    rules: "required",
                    message: i18n.t("validation_blank")
                },
                {
                    name: "password[password]",
                    rules: "min_length[5]",
                    message: i18n.t("validation_length_min", {
                        postProcess: "sprintf",
                        sprintf: ["5"]
                    })
                },
                {
                    name: "password[confirm]",
                    rules: "matches[password[password]]",
                    message: i18n.t("password_match")
                }
            ]
        });
    });
});
