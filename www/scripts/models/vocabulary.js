/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "underscore",
    "backbone"
], function (_, Backbone) {
    var VocabularyModel = Backbone.Model.extend({
        urlRoot: function () {
            return "/admin/fields/vocabularies";
        }
    });

    // Return the model for the module
    return VocabularyModel;
});
