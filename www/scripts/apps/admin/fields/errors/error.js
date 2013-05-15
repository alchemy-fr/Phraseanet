define([
    "jquery",
    "underscore"
], function($, _) {

    var Error = function (model, fieldId, message) {
        this.model = model;
        this.fieldId = fieldId;
        this.message = message;
    };

    return Error;
});
