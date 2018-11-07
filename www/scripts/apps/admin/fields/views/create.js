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
    "bootstrap",
    "apps/admin/fields/views/alert",
    "models/field"
], function ($, _, Backbone, i18n, bootstrap, AlertView, FieldModel) {
    var CreateView = Backbone.View.extend({
        tagName: "div",
        events: {
            "click .btn-submit-field": "createAction",
            "click .btn-add-field": "toggleCreateFormAction",
            "click .btn-cancel-field": "toggleCreateFormAction",
            "keyup input": "onKeyupInput"
        },
        template: _.template($("#create_template").html()),
        render: function () {

            this.$el.html(this.template());

            $("#new-source", this.$el).autocomplete({
                minLength: 2,
                source: function (request, response) {
                    $.ajax({
                        url: "/admin/fields/tags/search",
                        dataType: "json",
                        data: {
                            term: request.term
                        },
                        success: function (data) {
                            response($.map(data, function (item) {
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
        onKeyupInput: function (event) {
            $(event.target)
                .closest(".control-group")
                .removeClass("error")
                .find(".help-block")
                .empty();
        },
        createAction: function (event) {
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
            if ("undefined" !== typeof AdminFieldApp.fieldsCollection.find(function (model) {
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


            if (false === /^[a-zA-Z]([a-zA-Z0-9]+)$/i.test(fieldNameValue)) {
                fieldName
                    .closest(".control-group")
                    .addClass("error")
                    .find(".help-block")
                    .empty()
                    .append(i18n.t("validation_name_invalid"));

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

            if (formErrors > 0) {
                return;
            }

            var field = new FieldModel({
                "sbas-id": AdminFieldApp.sbas_id,
                "name": fieldNameValue,
                "tag": fieldTagValue,
                "label_en": $("#new-label_en", this.$el).val(),
                "label_fr": $("#new-label_fr", this.$el).val(),
                "label_de": $("#new-label_de", this.$el).val(),
                "label_nl": $("#new-label_nl", this.$el).val(),
                "multi": $("#new-multivalued", this.$el).is(":checked"),
                "report": false
            });

            field.save(null, {
                success: function (field, response, options) {
                    AdminFieldApp.fieldsCollection.add(field);
                    _.last(AdminFieldApp.fieldListView.itemViews).clickAction().animate();

                    new AlertView({alert: "info", message: i18n.t("created_success", {
                        postProcess: "sprintf",
                        sprintf: [field.get("name")]
                    })
                    }).render();
                },
                error: function (xhr, textStatus, errorThrown) {
                    new AlertView({
                            alert: "error", message: '' !== xhr.responseText ? xhr.responseText : i18n.t("something_wrong")}
                    ).render();

                    self.toggleCreateFormAction();
                }
            });

            return this;
        },
        toggleCreateFormAction: function (event) {
            var fieldBlock = $(".add-field-block", this.$el);
            var addBtn = $(".btn-add-field", this.$el);

            fieldBlock.is(":hidden") ? fieldBlock.show() : fieldBlock.hide();

            addBtn.prop('disabled', !fieldBlock.is(":hidden"));

            AdminFieldApp.resizeListBlock();

            return this;
        }
    });

    return CreateView;
});
