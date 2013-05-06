define([
    'underscore',
    'backbone'
], function(_, Backbone) {
    var FieldModel = Backbone.Model.extend({
        urlRoot: '/admin/fields/1/fields'
    });

    // Return the model for the module
    return FieldModel;
});
