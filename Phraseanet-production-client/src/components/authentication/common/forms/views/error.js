/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';
import _ from 'underscore';
import Backbone from 'backbone';
var ErrorView = Backbone.View.extend({
    tagName: 'div',
    initialize: function (options) {
        options = options || {};

        if (options.name  === undefined) {
            throw 'Missing name attribute in error view';
        }

        if (options.errorTemplate === undefined) {
            throw 'Missing errorTemplate attribute in error view';
        }

        this.name = options.name;
        this.errorTemplate = options.errorTemplate;

        this.errors = options.errors || {};
        this.onRenderError = options.onRenderError || null;
    },
    render: function () {
        if (this.errors.length > 0) {
            var template = _.template($(this.errorTemplate).html(), {
                errors: this.errors
            });

            this.$el.html(template);

            if (_.isFunction(this.onRenderError)) {
                this.onRenderError(this.name, this.$el);
            }
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
    reset: function () {
        this.$el.empty();
    }
});

export default ErrorView;
