define([
    'underscore',
    'backbone',
    'i18n'
], function( _, Backbone, i18n, bootstrap) {
    var DcFieldsView = Backbone.View.extend({
        tagName: "div",
        className: "input-append",
        events: {
            "change select": "selectChangedAction"
        },
        render: function() {
            var template = _.template($("#dc_fields_template").html(), {
                dces_elements: this.collection.toJSON()
            });

            this.$el.html(template);

            return this;
        },
        selectChangedAction: function(e) {
            var index = $(e.target)[0].selectedIndex;
            this.$el.closest('table').find('.dces-help-block').empty().append(this.collection.at(index).get('definition'));
        }
    });

   return DcFieldsView;
});
