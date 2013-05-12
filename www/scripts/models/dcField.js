define([
    'underscore',
    'backbone'
], function(_, Backbone) {
    var DcFieldModel = Backbone.Model.extend({
        urlRoot: function () {
            return '/admin/fields/dc-fields';
        }
    });

    // Return the model for the module
    return DcFieldModel;
});
