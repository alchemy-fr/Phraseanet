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
    "jquery.treeview"
], function ($, _, Backbone) {
    var LeftPanelView = Backbone.View.extend({
        initialize: function (options) {
            options = options || {};
            if (false === ("eventManager" in options)) {
                throw "You must set an event manager";
            }
            if (false === ("treeUrl" in options)) {
                throw "You must set the tree url";
            }
            this.delegateEvents(this.events);
            this.eventManager = options.eventManager;
            this.treeUrl = options.treeUrl;
            this.$treeview = $("#FNDR", this.$el);

            var $this = this;
            this.eventManager.on("panel:left:success", function(data, click) {
                $this.render(data);
                $this.activeTree();
                if (click) {
                    $this.clickSelected();
                }
            })
        },
        render : function(data) {
            this.$treeview.empty().append(data);
        },
        events: {
            "click a[target=right]": "clickAction"
        },
        activeTree: function() {
            this.$treeview.treeview({
                collapsed: true,
                animated: "medium"
            });
        },
        clickSelected: function () {
            if($('.selected', this.$el).length > 0) {
                $('.selected a', this.$el).trigger('click');
            } else {
                $('.zone_online_users', this.$el).trigger('click');
            }
        },
        reloadTree: function (position, click) {
            var $this = this;
            $.ajax({
                type: "GET",
                url: "/admin/tree/",
                data: {position : position},
                success: function(data){
                    $this.eventManager.trigger('panel:left:success', data , click)
                },
                beforeSend: function(){
                    $this.eventManager.trigger('panel:left:beforeSend');
                },
                complete: function(){
                    $this.eventManager.trigger('panel:left:complete');
                }
            });
        },
        clickAction: function(event) {
            event.preventDefault();
            var $this = this;
            var link = $(event.currentTarget);

            $.ajax({
                type: "GET",
                url: link.attr('href'),
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

            if ("undefined" !== typeof link.data("ws-topic")) {
                SubscriberManager.register(link.data("ws-topic"));
            }

            $this.selectLink(link);
        },
        selectLink: function(link) {
            $('.selected', this.$el).removeClass('selected');
            link.parent().addClass('selected');
        }
    });

    return LeftPanelView;
});
