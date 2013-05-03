define([
    'jquery',
    'underscore',
    'backbone',
    'i18n'
], function($, _, Backbone, i18n) {
    var FieldEditView = Backbone.View.extend({
        tagName: "div",
        className: "field-edit",
        render: function() {
            this.el.innerHTML = '';
            this.el.innerHTML = Twig.render(fieldsEdit, {
                field: this.model.attributes
            });

            return this;
        }
    });

    return FieldEditView;
});
