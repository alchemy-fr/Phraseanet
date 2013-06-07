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
    "backbone"
], function($, _, Backbone) {
    var ErrorView = Backbone.View.extend({
        tagName: "div",
        initialize: function(options) {
            if (options) {
                this.errors = options.errors || {};
            } else {
                this.errors = {};
            }
        },
        render: function() {
            if (this.errors.length > 0 ) {
                var template = _.template($("#field_errors").html(), {
                    errors: this.errors
                });

                this.$el.html(template);
            } else {
                this.reset();
            }

            return this;
        },
        renderErrors: function (errors) {
            this.errors = errors;
            this.render();

            return this;
        },
        reset: function() {
             this.$el.empty();
        }
    });

    return ErrorView;
});
