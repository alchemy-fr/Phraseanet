define([
    'underscore',
    'backbone',
    'models/admin/field'
], function(_, Backbone, FieldModel) {
    var FieldCollection = Backbone.Collection.extend({
        model: FieldModel,
        url: '/admin/fields/1/fields'
    });

    return FieldCollection;
});
