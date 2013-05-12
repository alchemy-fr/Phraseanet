define([
    'underscore',
    'backbone',
    'i18n'
], function( _, Backbone, i18n, bootstrap) {
    var AddView = Backbone.View.extend({
        tagName: "div",
        className: "add-field-block",
        style: "display:none;",
        events: {
            "click .btn-submit-field": "createAction"
        },
        initialize: function() {},
        render: function() {
            var template = _.template($("#alert_template").html(), {
                msg: this.msg
            });

            this.$el.after(template);

            return this;
        },
        createAction: function(event) {
            var self = this;

            var field = new FieldModel({
                "name": "AA" + new Date().getUTCMilliseconds(),
                "tag": "IPTC:ObjectName"
            }, {
                sbas_id: AdminFieldApp.sbas_id
            });

            field.save(null, {
                success: function(field) {
                    self.collection.add(field);
                    _.last(self.itemViews).clickAction().animate();
                    new AlertView({alert: 'success', message: 'A new field has been created'}).render();
                },
                error: function() {
                    new AlertView({alert: 'error', message: 'Something wrong happened'}).render();
                }
            });
        },
        destroy: function() {
            this.remove();
        }
    });

   return AddView;
});

