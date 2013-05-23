define([
    "jquery",
    "underscore",
    "backbone",
    "i18n",
    "bootstrap",
    "apps/admin/fields/views/create",
    "models/field"
], function($, _, Backbone, i18n, bootstrap, AlertView, FieldModel) {
    var CreateView = Backbone.View.extend({
        tagName: "div",
        events: {
            "click .btn-submit-field": "createAction",
            "click .btn-add-field": "toggleCreateFormAction",
            "click .btn-cancel-field": "toggleCreateFormAction"
        },
        render: function() {
            var template = _.template($("#create_template").html());

            this.$el.html(template);

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
            if ("undefined" !== typeof AdminFieldApp.fieldsCollection.find(function(model){
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
                "sorter": AdminFieldApp.fieldsCollection.max(function(model) {
                    return model.get("sorter");
                }).get("sorter") + 1
            });

            field.save(null, {
                success: function(field, response, options) {
                    if (response.success) {
                        AdminFieldApp.fieldsCollection.add(field);
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
            var addBtn = $(".btn-add-field", this.$el);

            fieldBlock.is(":hidden") ? fieldBlock.show() : fieldBlock.hide();

            addBtn.attr('disabled', !fieldBlock.is(":hidden"));

            AdminFieldApp.resizeListBlock();

            return this;
        }
    });

    return CreateView;
});
