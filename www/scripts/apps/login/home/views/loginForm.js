/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "jquery",
    "underscore",
    "backbone",
    "bootstrap",
    "common/validator",
    "apps/login/home/views/inputView"
], function($, _, Backbone, bootstrap, Validator, InputView) {
    var LoginFormView = Backbone.View.extend({
        events: {
            "submit": "onSubmit"
        },
        initialize: function(options) {
            var self = this;
            var rules = [];

            if (options) {
                rules = options.rules || [];
            }
            // get a new validator defined rules
            this.validator = new Validator(rules);

            this.inputViews = {};

            // creates input views for each input
            _.each(this.$el.serializeArray(), function (input) {
                var name = input.name;
                self.inputViews[name] = new InputView({
                    name: name,
                    el : $("input[name="+name+"]", self.$el).closest("div")
                });
            });
        },
        onSubmit: function (event) {
            var self = this;

            // reset previous errors in the view
            this._resetInputErrors();

            // validate new values
            this.validator.validate(this.$el.serializeArray());

            if (this.validator.hasErrors()) {
                // cancel submit
                event.preventDefault();
                // group errors by input
                _.each(_.groupBy(this.validator.getErrors(), function(error){
                    return error.name;
                }), function (errors, name) {
                    // show new errors in the views
                    self.inputViews[name].showErrors(errors);
                });
            }
        },
        _resetInputErrors: function() {
            _.each(this.inputViews, function(view) {
                view.resetErrors();
            });
        }
    });

    return LoginFormView;
});
