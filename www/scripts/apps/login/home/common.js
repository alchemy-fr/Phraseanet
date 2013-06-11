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
    "backbone",
    "bootstrap",
    "multiselect"
], function($, _, Backbone, bootstrap, multiselect) {
    var initialize = function() {
        // close alerts
        $(document).on("click", ".alert .alert-block-close a", function(e){
            e.preventDefault();
            $(this).closest('.alert').alert('close');
            return false;
        });

        $("select[multiple='multiple']").multiselect({
            buttonWidth : "100%",
            buttonClass: 'btn btn-inverse'
        });
    };

    return {
        initialize: initialize,
        languagePath: '/login/language.json'
    };
});
