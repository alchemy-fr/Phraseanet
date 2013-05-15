define([
    "jquery",
    "jqueryui",
    "underscore",
    "backbone",
    "i18n",
    "apps/admin/fields/views/listRow",
    "apps/admin/fields/views/alert",
    "models/field"
], function($, jqueryui, _, Backbone, i18n, FieldListRowView, AlertView, FieldModel) {
    var FieldListView = Backbone.View.extend({
        events: {
            "keyup #live_search": "searchAction",
            "click .btn-submit-field": "createAction",
            "click .btn-add-field": "toggleCreateFormAction",
            "click .btn-cancel-field": "toggleCreateFormAction",
            "update-sort": "updateSortAction"
        },
        initialize: function() {
            var self = this;
            // store all single rendered views
            this.itemViews = [];

            // rerender whenever there is a change on the collection
            this.collection.bind("reset", this.render, this);
            this.collection.bind("add", this.render, this);
            this.collection.bind("remove", this.render, this);

            AdminFieldApp.errorManager.on('add-error', function(error) {
                var model = error.model;
                var itemView = _.find(self.itemViews, function(view) {
                    return model.get('id') === view.model.get('id');
                });

                if ('undefined' !== typeof itemView) {
                    itemView.error(true);
                }
            });

            AdminFieldApp.errorManager.on('remove-error', function(model) {
                var itemView = _.find(self.itemViews, function(view) {
                    return model.get('id') === view.model.get('id');
                });

                if ('undefined' !== typeof itemView) {
                    itemView.error(false);
                }
            });
        },
        render: function() {
            var template = _.template($("#item_list_view_template").html(), {});

            this.$el.empty().html(template);

            this.$listEl = $("ul#collection-fields", this.$el);

            this._renderList(this.collection);

            $("#new-source", this.$el).autocomplete({
                minLength: 2,
                source: function(request, response) {
                    $.ajax({
                        url: "/admin/fields/tags/search",
                        dataType: "json",
                        data: {
                            term: request.term
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    label: item.label,
                                    value: item.value
                                };
                            }));
                        }
                    });
                }
            }).autocomplete("widget").addClass("ui-autocomplete-admin-field");

            AdminFieldApp.resizeListBlock();

            return this;
        },
        // render list by appending single item view, also fill itemViews
        _renderList: function(fields) {
            var that = this;

            this.$listEl.empty();
            this.itemViews = [];

            fields.each(function(field) {
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
                start: function(event, ui) {
                    ui.item.addClass("border-bottom");

                },
                stop: function(event, ui) {
                    ui.firstItemPosition = $("li:first", $(this).sortable('widget')).position().top;
                    ui.item.trigger("drop", ui);
                }
            });

            this.$listEl.disableSelection();

            this.$listEl.find("li:last").addClass("last");

            return this;
        },
        searchAction: function(event) {
            this._renderList(this.collection.search($("#live_search", this.$el).val()));

            return this;
        },
        createAction: function(event) {
            var self = this;
            var formErrors = 0;

            var fieldName = $("#new-name", this.$el);
            var fieldNameValue = fieldName.val();
            var fieldTag = $("#new-source", this.$el);
            var fieldTagValue = fieldTag.val();

            // check for empty field name
            if ("" === fieldNameValue) {
                fieldName
                    .closest(".control-group")
                    .addClass("error")
                    .find(".help-block")
                    .empty()
                    .append(i18n.t("validation_blank"));

                formErrors++;
            }

            // check for duplicate field name
            if ("undefined" !== typeof this.collection.find(function(model){
                return model.get("name").toLowerCase() === fieldNameValue.toLowerCase();
            })) {
                fieldName
                    .closest(".control-group")
                    .addClass("error")
                    .find(".help-block")
                    .empty()
                    .append(i18n.t("validation_name_exists"));

                formErrors++;
            }

            // check for format tag
            if ("" !== fieldTagValue && false === /[a-z]+:[a-z0-9]+/i.test(fieldTagValue)) {
                fieldTag
                    .closest(".control-group")
                    .addClass("error")
                    .find(".help-block")
                    .empty()
                    .append(i18n.t("validation_tag_invalid"));

                formErrors++;
            }

            if (formErrors > 0 ) {
                return;
            }

            var field = new FieldModel({
                "sbas-id": AdminFieldApp.sbas_id,
                "name": fieldNameValue,
                "tag": fieldTagValue,
                "multi": $("#new-multivalued", this.$el).is(":checked"),
                "sorter": this.collection.max(function(model) {
                    return model.get("sorter");
                }).get("sorter") + 1
            });

            field.save(null, {
                success: function(field, response, options) {
                    if (response.success) {
                        self.collection.add(field);
                        _.last(self.itemViews).clickAction().animate();
                    }

                    new AlertView({
                        alert: response.success ? "success" : "error", message: response.message
                    }).render();
                },
                error: function(model, xhr, options) {
                    new AlertView({
                        alert: "error", message: i18n.t("something_wrong")}
                    ).render();

                    self.toggleCreateFormAction();
                }
            });

            return this;
        },
        toggleCreateFormAction: function(event) {
            var fieldBlock = $(".add-field-block", this.$el);

            fieldBlock.is(":hidden") ? fieldBlock.show() : fieldBlock.hide();
            AdminFieldApp.resizeListBlock();

            return this;
        },
        updateSortAction: function(event, model, ui) {
            var position = ui.item.index();
            this.collection.remove(model, {silent: true});

            // reorder all collection model
            this.collection.each(function(model, index) {
                var ordinal = index;
                if (index >= position) ordinal += 1;
                model.set("sorter", ordinal);
            });

            model.set("sorter", position);
            this.collection.add(model, {at: position});

            this.itemViews[0].animate(Math.abs(ui.firstItemPosition));

            // update edit view model
            AdminFieldApp.fieldEditView.model = this.collection.find(function(el) {
                return el.get("id") === AdminFieldApp.fieldEditView.model.get("id");
            });

            return this;
        }
    });

    return FieldListView;
});
