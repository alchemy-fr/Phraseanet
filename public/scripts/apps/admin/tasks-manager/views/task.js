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
    var TaskView = Backbone.View.extend({
        template: _.template($('#task_template').html()),
        initialize: function () {
            // render only parts of the model
            this.model.on('change:id', this.renderId, this);
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
            this.$el.html(this.template({'task':this.model.toJSON()}));
            return this;
        },
        renderId: function () {
            $(".idTask", this.$el).html(this.model.get("id"));
            return this;
        },
        renderConfiguration: function () {
            $(".confTask", this.$el).html(this.model.get("configuration"));
            return this;
        },
        renderActual: function () {
            $(".actualTask", this.$el).html(this.model.get("actual"));
            return this;
        },
        renderPid: function () {
            $(".pidTask", this.$el).html(this.model.get("process-id"));
            return this;
        },
        renderName: function () {
            $(".nameTask", this.$el).html(this.model.get("name"));
            return this;
        },
        clickAction: function(e) {
            e.preventDefault();
            var link = $(e.target);
            var url =  link.attr('href');

            if(url && url.indexOf('#') !== 0) {
                // This is defined in admin/index.html.twig
//                window.loadRightAjax(url, link.attr("method") || "GET");
            }
        }
    });

    return TaskView;
});
