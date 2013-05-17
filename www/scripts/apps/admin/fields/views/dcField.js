define([
    "jquery",
    "underscore",
    "backbone",
    "i18n"
], function($, _, Backbone, i18n, bootstrap) {
    var DcFieldsView = Backbone.View.extend({
        tagName: "div",
        className: "input-append",
        initialize : function (options) {
            this.field = options.field;
        },
        render: function() {
            var template = _.template($("#dc_fields_template").html(), {
                dces_elements: this.collection.toJSON(),
                field: this.field.toJSON()
            });

            this.$el.html(template);

            var index = $("#dces-element", AdminFieldApp.$rightBlock)[0].selectedIndex - 1;
            if (index > 0 ) {
                $(".dces-help-block", AdminFieldApp.$rightBlock).html(
                    this.collection.at(index).get("definition")
                );
            }

            return this;
        }
    });

   return DcFieldsView;
});
