/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require([
    "jquery",
    "i18n",
    "apps/login/home/common",
    "apps/login/home/views/form"
], function($, i18n, Common, RenewPassword) {
    i18n.init({
            resGetPath: Common.languagePath,
            useLocalStorage: true
    }, function() {
        Common.initialize();

        new RenewPassword({
            el : $("form[name=passwordRenewForm]"),
            rules: [{
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
            }]
        });
    });
});
