/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "jquery",
    "underscore",
    "backbone"
], function ($, _, Backbone) {
    var PingView = Backbone.View.extend({
        render: function () {
            var date = new Date();

            this.$el.html(date.toISOString());

            return this;
        }
    });

    return PingView;
});
