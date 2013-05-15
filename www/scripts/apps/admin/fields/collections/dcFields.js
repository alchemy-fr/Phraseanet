define([
    "underscore",
    "backbone",
    "models/dcField"
], function(_, Backbone, DcFieldModel) {
    var DcFieldCollection = Backbone.Collection.extend({
        model: DcFieldModel,
        url: function() {
            return "/admin/fields/dc-fields";
        },
        comparator: function(item) {
            return item.get("label");
        }
    });

    return DcFieldCollection;
});
