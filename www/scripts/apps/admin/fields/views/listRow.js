define([
    'jquery',
    'underscore',
    'backbone'
], function($, _, Backbone) {
    var FieldListRowView = Backbone.View.extend({
        tagName: "li",
        className: "field-row",
        render: function() {
            this.el.innerHTML = Twig.render(fieldsRow, {
                id: this.model.get('id'),
                name: this.model.get('name'),
                tag: this.model.get('tag')
            });

            return this;
        }
    });

    return FieldListRowView;
});
