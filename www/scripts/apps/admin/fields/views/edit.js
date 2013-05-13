define([
    'underscore',
    'backbone',
    'i18n',
    'apps/admin/fields/views/alert',
    'apps/admin/fields/views/modal',
    'apps/admin/fields/views/dcField',
], function(_, Backbone, i18n, AlertView, ModalView, DcFieldView) {
    var FieldEditView = Backbone.View.extend({
        tagName: "div",
        className: "field-edit",
        initialize: function() {
            this.model.on('change', this.render, this);
            this.model.on('change:name', this.onModelFieldChange, this);
            this.model.on('change:tag', this.onModelFieldChange, this);

            this.dcFieldsSubView = new DcFieldView({
                collection: window.AdminFieldApp.dcFieldsCollection
            });
        },
        render: function() {
            var template = _.template($("#edit_template").html(), {
                field: this.model.toJSON(),
                vocabularyTypes: window.AdminFieldApp.vocabularyCollection.toJSON()
            });

            this.$el.empty().html(template);

            this.assign({
                '.dc-fields-subview' : this.dcFieldsSubView
            });

            $("#tag", this.$el).autocomplete({
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
            }).val(this.model.get('tag')).autocomplete("widget").addClass("ui-autocomplete-admin-field");

            return this;
        },
        events: {
            "click .delete-field": "deleteAction",
            "focusout input[type=text]": "fieldChangedAction",
            "change input[type=checkbox]": "fieldChangedAction",
            "change select": "selectionChangedAction"
        },
        selectionChangedAction: function(e) {
            var field = $(e.currentTarget);
            var value = $("option:selected", field).val();
            var data = {};
            data[field.attr('id')] = value;
            this.model.set(data);
        },
        fieldChangedAction: function(e) {
            var field = $(e.currentTarget);
            var data = {};
            data[field.attr('id')] = field.is(":checkbox") ? field.is(":checked") : field.val();
            this.model.set(data);
        },
        deleteAction: function() {
            var self = this;
            var modalView = new ModalView({
                model: this.model,
                message: i18n.t("are_you_sure_delete", { postProcess: "sprintf", sprintf: [this.model.get('name')] })
            });
            var previousIndex = AdminFieldApp.fieldListView.collection.previousIndex(this.model);
            var nextIndex =  AdminFieldApp.fieldListView.collection.nextIndex(this.model);
            var itemView;

            if (previousIndex) {
                itemView = AdminFieldApp.fieldListView.itemViews[previousIndex];
            } else if (nextIndex) {
                itemView = AdminFieldApp.fieldListView.itemViews[nextIndex];
            }

            modalView.render();
            modalView.on('modal:confirm', function() {
                self.model.destroy({
                    success: function(model, response) {
                        AdminFieldApp.fieldListView.collection.remove(self.model);

                        if (itemView) {
                            itemView.clickAction().animate();
                        }

                        new AlertView({alert: 'info', message: i18n.t("deleted_success", { postProcess: "sprintf", sprintf: [model.get('name')] })}).render();
                    },
                    error: function(model, xhr) {
                        new AlertView({alert: 'error', message: i18n.t("something_wrong")}).render();
                    }
                });
            });

            return this;
        },
        onModelFieldChange: function() {
            AdminFieldApp.fieldListView.collection.remove(this.model, {silent: true});
            AdminFieldApp.fieldListView.collection.add(this.model);
            this.render();
        },
        assign: function(selector, view) {
            var selectors;
            if (_.isObject(selector)) {
                selectors = selector;
            } else {
                selectors = {};
                selectors[selector] = view;
            }
            if (!selectors) return;
            _.each(selectors, function(view, selector) {
                view.setElement(this.$(selector)).render();
            }, this);
        }
    });

    return FieldEditView;
});
