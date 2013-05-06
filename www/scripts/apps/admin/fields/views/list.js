define([
    'jquery',
    'underscore',
    'backbone',
    'apps/admin/fields/views/listRow'
], function($, _, Backbone, FieldListRowView) {
    var FieldListView = Backbone.View.extend({
        initialize: function() {
            var that = this;
            this._fieldViews = [];

            this.collection.each(function(field) {
                that._fieldViews.push(new FieldListRowView({
                    model: field
                }));
            });
        },
        render: function() {
            var that = this;
            $(this.el).empty();

            // Render each sub-view and append it to the parent view's element.
            _(this._fieldViews).each(function(singleView) {
                $(that.el).append(singleView.render().el);
            });

            return this;
        }
    });

    return FieldListView;
});
