define([
    'underscore',
    'backbone',
    'i18n',
    'bootstrap'
], function(_, Backbone, i18n, bootstrap) {
    var ModalView = Backbone.View.extend({
        tagName: "div",
        className: "modal",
        events: {
            'click .confirm': 'confirmAction'
        },
        initialize: function (options) {
            var self = this;
            // remove view when modal is closed
            this.$el.on('hidden', function() {
                self.remove();
            });

            if (options) {
                this.message = options.message;
            }
        },
        render: function() {
            var template = _.template($("#modal_delete_confirm_template").html(), {
                msg: this.message || ''
            });

            this.$el.html(template).modal();

            return this;
        },
        confirmAction: function () {
            this.trigger('modal:confirm');
            this.$el.modal('hide');
            this.remove();

            return this;
        }
    });

   return ModalView;
});
