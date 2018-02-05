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
    var SchedulerView = Backbone.View.extend({
        template: _.template($('#scheduler_template').html()),
        initialize: function () {
            // render only parts of the model
            this.model.on('change:configuration', this.renderConfiguration, this);
            this.model.on('change:actual', this.renderActual, this);
            this.model.on('change:process-id', this.renderPid, this);
            this.model.on('change:name', this.renderName, this);
        },
        events: {
            "click a": "clickAction"
        },
        tagName: "tr",
        render: function () {
            this.$el.empty();
            this.$el.html(this.template({'scheduler':this.model.toJSON()}));
            $('.dropdown-toggle').dropdown();
            return this;
        },
        renderConfiguration: function () {
            $(".confScheduler", this.$el).html(this.model.get("configuration"));
            return this;
        },
        renderActual: function () {
            $(".actualScheduler", this.$el).html(this.model.get("actual"));
            return this;
        },
        renderPid: function () {
            $(".pidScheduler", this.$el).html(this.model.get("process-id"));
            return this;
        },
        renderName: function () {
            $(".nameScheduler", this.$el).html(this.model.get("name"));
            return this;
        },
        clickAction: function(e) {
            e.preventDefault();
            var link = $(e.target);
            var url =  link.attr('href');

            if(url && url.indexOf('#') !== 0) {
                // This is defined in admin/index.html.twig
                // window.loadRightAjax(url, link.attr("method") || "GET");
            }
        }
    });

    return SchedulerView;
});
