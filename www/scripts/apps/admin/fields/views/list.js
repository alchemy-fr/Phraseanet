/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "jquery",
    "underscore",
    "backbone",
    "i18n",
    "common/multiviews",
    "apps/admin/fields/views/listRow",
    "apps/admin/fields/views/create",
    "jquery.ui"
], function ($, _, Backbone, i18n, MultiViews, FieldListRowView, CreateView) {
    var FieldListView = Backbone.View.extend(_.extend({}, MultiViews, {
        events: {
            "keyup #live_search": "searchAction",
            "update-sort": "updateSortAction"
        },
        template: _.template($("#item_list_view_template").html()),
        initialize: function () {
            var self = this;
            // store all single rendered views
            this.itemViews = [];

            // force base 1 indexed
            if (this.collection.length > 0 && this.collection.first().get("sorter") === 0) {
                this.collection.each(function (model) {
                    model.set({'sorter': model.get("sorter") + 1}, {silent: true});
                });
            }

            // rerender whenever there is a change on the collection
            this.collection.bind("reset", this.render, this);
            this.collection.bind("add", this.render, this);
            this.collection.bind("remove", this._onRemove, this);
            this.collection.bind("remove", this.render, this);

            AdminFieldApp.errorManager.on('add-error', function (error) {
                var model = error.model;
                var itemView = _.find(self.itemViews, function (view) {
                    return model.get('id') === view.model.get('id');
                });

                if ('undefined' !== typeof itemView) {
                    itemView.error(true);
                }
            });

            AdminFieldApp.errorManager.on('remove-error', function (model) {
                var itemView = _.find(self.itemViews, function (view) {
                    return model.get('id') === view.model.get('id');
                });

                if ('undefined' !== typeof itemView) {
                    itemView.error(false);
                }
            });
        },
        render: function () {
            this.$el.empty().html(this.template());

            this.$listEl = $("ul#collection-fields", this.$el);

            this._renderList(this.collection);

            this._assignView({
                ".create-subview": new CreateView()
            });

            AdminFieldApp.resizeListBlock();

            return this;
        },
        // render list by appending single item view, also fill itemViews
        _renderList: function (fields) {
            var that = this;

            this.$listEl.empty();
            this.itemViews = [];

            fields.each(function (field) {
                var fieldErrors = AdminFieldApp.errorManager.getModelError(field);

                var singleView = new FieldListRowView({
                    model: field,
                    id: "field-" + field.get("id")
                }).error(fieldErrors && fieldErrors.count() > 0);

                that.$listEl.append(singleView.render().el);
                that.itemViews.push(singleView);
            });

            this.$listEl.sortable({
                handle: ".handle",
                placeholder: "item-list-placeholder",
                start: function (event, ui) {
                    ui.item.addClass("border-bottom");
                },
                stop: function (event, ui) {
                    ui.firstItemPosition = $("li:first", $(this).sortable('widget')).position().top;
                    ui.item.trigger("drop", ui);
                }
            });

            this.$listEl.disableSelection();

            this.$listEl.find("li:last").addClass("last");

            return this;
        },
        searchAction: function (event) {
            this._renderList(this.collection.search($("#live_search", this.$el).val()));

            return this;
        },
        _onRemove: function (model) {
            var models = [];
            this.collection.each(function (m) {
                if (m.get("sorter") > model.get("sorter")) {
                    m.set("sorter", m.get("sorter") - 1);
                }

                models.push(m);
            });

            this.collection.reset(models);
        },
        updateSortAction: function (event, model, ui) {
            var newPosition = ui.item.index();
            this.collection.remove(model, {silent: true});
            this.collection.each(function (model, index) {
                var ordinal = index;
                if (index >= newPosition) ordinal += 2; else ordinal += 1;
                model.set({'sorter': ordinal}, {silent: true});
            });
            model.set({'sorter': newPosition + 1}, {silent: true});
            this.collection.add(model, {at: newPosition});

            this.itemViews[0].animate(Math.abs(ui.firstItemPosition));
            // update edit view model
            AdminFieldApp.fieldEditView.updateModel(this.collection.find(function (el) {
                return el.get("id") === AdminFieldApp.fieldEditView.model.get("id");
            }));

            AdminFieldApp.fieldEditView.render();
            AdminFieldApp.saveView.updateStateButton();

            return this;
        }
    }));

    return FieldListView;
});
