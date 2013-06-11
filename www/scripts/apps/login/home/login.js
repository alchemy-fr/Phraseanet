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
], function($, i18n, Common, LoginForm) {
    Common.initialize();

    i18n.init({
        resGetPath: Common.languagePath
    }, function() {
          new LoginForm({
            el : $("form[name=loginForm]"),
            rules: [{
                name: "login",
                rules: "required",
                message: i18n.t("validation_blank")
            },{
                name: "password",
                rules: "required",
                message: i18n.t("validation_blank")
            }]
        });
    });
});
