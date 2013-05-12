define([
    'jqueryui',
    'underscore',
    'backbone',
    'i18n',
    'apps/admin/fields/views/listRow',
    'apps/admin/fields/views/alert',
    'models/field'
], function(jqueryui, _, Backbone, i18n, FieldListRowView, AlertView, FieldModel) {
    var FieldListView = Backbone.View.extend({
        events: {
            "keyup #live_search": "searchAction",
            "click .btn-submit-field": "createAction",
            "click .btn-add-field": "toggleCreateFormAction",
            "click .btn-cancel-field": "toggleCreateFormAction",
            "update-sort": "onUpdateSort"
        },
        initialize: function() {
            // Store all single rendered views
            this.itemViews = [];

            _.bindAll(this, "render");
            // rerender whenever there is a change on the collection
            this.collection.bind("reset", this.render, this);
            this.collection.bind("add", this.render, this);
            this.collection.bind("remove", this.render, this);
        },
        render: function() {
            var template = _.template($("#item_list_view_template").html(), {});

            this.$el.empty().html(template);

            this.$listEl = $("ul#collection-fields", this.$el);

            this._renderList(this.collection);

            $("#new-source", this.$el).autocomplete({
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
        _renderList: function(fields) {
            var that = this;

            this.$listEl.empty();
            this.itemViews = [];

            fields.each(function(field) {
                var singleView = new FieldListRowView({
                    model: field,
                    id: 'field-' + field.get('id')
                });
                that.$listEl.append(singleView.render().el);
                that.itemViews.push(singleView);
            });

            this.$listEl.sortable({
                handle: ".handle",
                start: function () {
                    console.log('start', AdminFieldApp.fieldEditView.model.get('id'));
                },
                stop: function(event, ui) {
                    ui.item.trigger('drop', ui.item.index());
                }
            });

            this.$listEl.disableSelection();

            this.$listEl.find('li:last').addClass('last');
            
            return this;
        },
        searchAction: function(event) {
            this._renderList(this.collection.search($("#live_search", this.$el).val()));
        },
        createAction: function(event) {
            var self = this;

            var fieldName = $("#new-name", this.$el);

            if ('' == fieldName.val()) {
                fieldName.closest('.control-group').addClass('error').find('.help-block').empty().append(i18n.t('validation_blank'));
                return;
            }

            var field = new FieldModel({
                "sbas-id": AdminFieldApp.sbas_id,
                "name": fieldName.val(),
                "tag": $("#new-source", this.$el).val(),
                "multi": $("#new-multivalued", this.$el).is(':checked')
            });

            field.save(null, {
                success: function(field, response, options) {
                    if (response.success) {
                        self.collection.add(field);
                        _.last(self.itemViews).clickAction().animate();
                        new AlertView({alert: 'success', message: response.message }).render();
                    } else {
                        new AlertView({alert: 'warning', message: response.message}).render();
                    }
                },
                error: function(model, xhr, options) {
                    new AlertView({alert: 'error', message: i18n.t("something_wrong")}).render();
                    self.toggleCreateFormAction();
                }
            });
        },
        toggleCreateFormAction: function(event) {
            $('.add-field-block', this.$el).toggle();
        },
        onUpdateSort: function(event, model, position) {
            this.collection.remove(model, {silent: true});

            this.collection.each(function(model, index) {
                var ordinal = index;
                if (index >= position) ordinal += 1;
                model.set('sorter', ordinal);
            });

            model.set('sorter', position);
            this.collection.add(model, {at: position});

            // update edit view
            AdminFieldApp.fieldEditView.model = this.collection.find(function(el) { return el.get('id') === AdminFieldApp.fieldEditView.model.get('id') });
        }
    });

    return FieldListView;
});
