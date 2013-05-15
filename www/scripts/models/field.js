define([
    "underscore",
    "backbone"
], function(_, Backbone) {
    var FieldModel = Backbone.Model.extend({
        initialize : function(attributes, options) {
            if (attributes && ! "sbas-id" in attributes) {
                throw "You must set a sbas id";
            }
        },
        urlRoot: function () {
            return "/admin/fields/"+ this.get("sbas-id") +"/fields";
        },
        defaults: {
            "business": false,
            "type": "string",
            "thumbtitle": "0",
            "tbranch": "",
            "separator": "",
            "required": false,
            "report": true,
            "readonly": false,
            "multi": false,
            "indexable": true,
            "dces-element": null,
            "vocabulary-type": null,
            "vocabulary-restricted": false
        }
    });

    // Return the model for the module
    return FieldModel;
});
