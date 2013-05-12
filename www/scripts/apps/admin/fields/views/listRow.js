define([
    'underscore',
    'backbone',
    'apps/admin/fields/views/edit',
    'apps/admin/fields/views/alert'
], function(_, Backbone, FieldEditView, AlertView) {
    var FieldListRowView = Backbone.View.extend({
        tagName: "li",
        className: "field-row",
        initialize: function() {
            // destroy view is model is deleted
            this.model.on('destroy', this.remove, this);
        },
        events : {
            "click .trigger-click": "clickAction",
            "drop" : "dropAction"
        },
        clickAction: function (e) {
            this.select();
            // first click create view else update model's view
            if (typeof AdminFieldApp.fieldEditView === 'undefined') {
                AdminFieldApp.fieldEditView = new FieldEditView({
                    el: $('.right-block')[0],
                    model: this.model
                });
            } else  {
                AdminFieldApp.fieldEditView.model = this.model;
            }

            AdminFieldApp.fieldEditView.render();

            return this;
        },
        dropAction: function(event, index) {
            this.$el.trigger('update-sort', [this.model, index]);
        },
        render: function() {
            var template = _.template($("#list_row_template").html(), {
                id: this.model.get('id'),
                position: this.model.get('sorter'),
                name: this.model.get('name'),
                tag: this.model.get('tag')
            });

            this.$el.empty().html(template);

            if (AdminFieldApp.fieldEditView && AdminFieldApp.fieldEditView.model.get('id') === this.model.get('id')) {
                this.select();
            }
            return this;
        },
        // set selected class
        select: function () {
            $("li", this.$el.closest('ul')).removeClass('selected');
            this.$el.addClass('selected');

            return this;
        },
        animate: function () {
            var offset = this.$el.offset();

            this.$el.closest('div').animate({
                scrollTop: offset.top - 20
            });
        }
    });

    return FieldListRowView;
});
