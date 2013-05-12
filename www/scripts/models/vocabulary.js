define([
    'underscore',
    'backbone'
], function(_, Backbone) {
    var VocabularyModel = Backbone.Model.extend({
        urlRoot: function () {
            return '/admin/fields/vocabularies';
        }
    });

    // Return the model for the module
    return VocabularyModel;
});
