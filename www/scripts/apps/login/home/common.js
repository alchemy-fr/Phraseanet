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
    "multiselect"
], function ($, _, i18n, Backbone, bootstrap, multiselect) {
    var initialize = function () {
        // close alerts
        $(document).on("click", ".alert .alert-block-close a", function (e) {
            e.preventDefault();
            $(this).closest('.alert').alert('close');
            return false;
        });

        $("select[multiple='multiple']").multiselect({
            buttonWidth: "100%",
            buttonClass: 'btn btn-inverse',
            maxHeight: 185,
            includeSelectAllOption: true,
            selectAllValue: 'all',
            selectAllText: i18n.t("all_collections"),
            buttonText: function (options, select) {
                if (options.length === 0) {
                    return i18n.t("no_collection_selected") + '<b class="caret"></b>';
                } else {
                    return i18n.t(
                        options.length === 1 ? "one_collection_selected" : "collections_selected", {
                            postProcess: "sprintf",
                            sprintf: [options.length]
                        }) + ' <b class="caret"></b>';
                }
            }
        });
        $('form[name="registerForm"]').on('submit', function () {
            // must deselect the "select all" checkbox for server side validation.
            $("select[multiple='multiple']").multiselect('deselect', 'all');
        });
    };

    return {
        initialize: initialize,
        languagePath: '/login/language.json'
    };
});
