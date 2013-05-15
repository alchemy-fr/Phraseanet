define([
    "underscore",
    "backbone",
    "models/vocabulary"
], function(_, Backbone, VocabularyModel) {
    var VocabularyCollection = Backbone.Collection.extend({
        model: VocabularyModel,
        url: function() {
            return "/admin/fields/vocabularies";
        },
        comparator: function(item) {
            return item.get("name");
        }
    });

    return VocabularyCollection;
});
