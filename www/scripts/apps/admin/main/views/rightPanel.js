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
    "backbone",
    "blueimp.loadimage",
    "jfu.iframe-transport",
    "jfu.fileupload",
    "jquery.ui",
    "bootstrap"
], function ($, _, Backbone) {
    var RightPanelView = Backbone.View.extend({
        initialize: function (options) {
            options = options || {};
            if (false === ("eventManager" in options)) {
                throw "You must set en event manager";
            }
            this.delegateEvents(this.events);
            this.eventManager = options.eventManager;

            var $this = this;
            this.eventManager.on("panel:right:beforeSend", function() {
                $this.$el.empty();
                $this.loadingState(true);
            });
            this.eventManager.on("panel:right:complete", function() {
                $this.loadingState(false);
            });
            this.eventManager.on("panel:right:success", function(data) {
                $this.render(data);
            })
        },
        events: {
            "submit form:not(.no-ajax)": "submitAction",
            "click a:not(.no-ajax)": "clickAction"
        },
        render: function (data) {
            this.$el.html(data);

            return this;
        },
        clickAction: function(event) {
            event.preventDefault();
            var $this = this;
            var link = $(event.currentTarget);
            var url = link.attr('href');

            if(url && url.indexOf('#') !== 0) {
                $.ajax({
                    type: link.attr("method") || "GET",
                    url: url,
                    success: function(data) {
                        $this.eventManager.trigger('panel:right:success', data);
                    },
                    beforeSend: function(){
                        $this.eventManager.trigger('panel:right:beforeSend');
                    },
                    complete: function(){
                        $this.eventManager.trigger('panel:right:complete');
                    }
                });
            }
        },
        submitAction: function(event) {
            event.preventDefault();
            var $this = this;
            var link = $(event.currentTarget);
            var url = link.attr('action');

            if(url) {
                $.ajax({
                    type: link.attr('method') || 'GET',
                    url: url,
                    data: link.serializeArray(),
                    success: function (data) {
                        $this.eventManager.trigger("panel:right:success", data);
                    },
                    beforeSend: function(){
                        $this.eventManager.trigger('panel:right:beforeSend');
                    },
                    complete: function(){
                        $this.eventManager.trigger('panel:right:complete');
                    }
                });
            }
        },
        loadingState: function(state) {
            if (state) {
                this.$el.addClass("loading");
            } else {
                this.$el.removeClass("loading");
            }
        }
    });

    return RightPanelView;
});
