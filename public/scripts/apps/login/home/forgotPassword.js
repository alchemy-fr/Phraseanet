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
], function ($, i18n, Common, ForgotPassWordForm) {
    Common.initialize();

    i18n.init({
        resGetPath: Common.languagePath,
        useLocalStorage: true
    }, function () {
        new ForgotPassWordForm({
            el: $("form[name=forgottenPasswordForm]"),
            rules: [
                {
                    name: "email",
                    rules: "required",
                    message: i18n.t("validation_blank")
                },
                {
                    name: "email",
                    rules: "valid_email",
                    message: i18n.t("validation_email")
                }
            ]
        });
    });
});
