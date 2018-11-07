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
    "backbone",
    "models/vocabulary"
], function (_, Backbone, VocabularyModel) {
    var VocabularyCollection = Backbone.Collection.extend({
        model: VocabularyModel,
        url: function () {
            return "/admin/fields/vocabularies";
        },
        comparator: function (item) {
            return item.get("name");
        }
    });

    return VocabularyCollection;
});
