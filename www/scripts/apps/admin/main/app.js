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
    "apps/admin/main/views/leftPanel",
    "apps/admin/main/views/rightPanel"
], function ($, _, Backbone, LeftPanel, RightPanel) {
    window.AdminApp = {
        $scope: $("#admin-app"),
        $leftView : $(".left-view", this.$scope),
        $rightView : $(".right-view", this.$scope),
        eventManager: _.extend({}, Backbone.Events)
    };

    var pullNotifications = function (){
        $.ajax({
            type: "POST",
            url:  AdminApp.$scope.data("notif-url"),
            dataType: 'json',
            data: {
                module : 3,
                usr : AdminApp.$scope.data("usr")
            },
            error: function(){
                window.setTimeout("pollNotifications();", 10000);
            },
            timeout: function(){
                window.setTimeout("pollNotifications();", 10000);
            },
            success: function(data){
                if (data) {
                    manageSession(data);
                }
                var t = 120000;
                if (data.apps && parseInt(data.apps) > 1) {
                    t = Math.round((Math.sqrt(parseInt(data.apps)-1) * 1.3 * 120000));
                }
                window.setTimeout("pollNotifications();", t);
            }
        });
    };

    var create = function() {
        AdminApp.LeftView = new LeftPanel({
            el: AdminApp.$leftView,
            eventManager: AdminApp.eventManager,
            treeUrl: AdminApp.$leftView.data("tree-url")
        });

        AdminApp.RightView = new RightPanel({
            el: AdminApp.$rightView,
            eventManager: AdminApp.eventManager
        });
    }

    var initialize = function (options) {
        if (false === "wsurl" in options) {
            throw "You must define a websocket url";
        }

        create();

        AdminApp.LeftView.activeTree();
        AdminApp.LeftView.clickSelected();

        window.setTimeout(function() {pullNotifications();}, 15000);
    };

    return {
        create: create,
        initialize: initialize
    };
});
