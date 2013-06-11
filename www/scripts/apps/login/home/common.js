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
    "i18n",
    "backbone",
    "bootstrap",
    "multiselect"
], function($, _, i18n, Backbone, bootstrap, multiselect) {
    var initialize = function() {
        // close alerts
        $(document).on("click", ".alert .alert-block-close a", function(e){
            e.preventDefault();
            $(this).closest('.alert').alert('close');
            return false;
        });

        $("select[multiple='multiple']").multiselect({
            buttonWidth : "100%",
            buttonClass: 'btn btn-inverse',
            buttonText: function(options, select) {
                if (options.length === 0) {
                    return i18n.t("none_selected") + '<b class="caret"></b>';
                }
                else if (options.length > 4) {
                    return options.length + (options.length > 1 ? i18n.t("collections") : i18n.t("collection")) + ' <b class="caret"></b>';
                }
                else {
                    var selected = '';
                    options.each(function() {
                        selected += $(this).text() + ', ';
                    });
                    return selected.substr(0, selected.length -2) + ' <b class="caret"></b>';
                }
            }
        });
    };

    return {
        initialize: initialize,
        languagePath: '/login/language.json'
    };
});
