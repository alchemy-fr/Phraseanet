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
    "common/websockets/connection",
    "apps/admin/main/views/leftPanel",
    "apps/admin/main/views/rightPanel"
], function ($, _, Backbone, WSConnection, LeftPanel, RightPanel) {
    window.AdminApp = {
        $scope: $("#admin-app"),
        $leftView : $(".left-view", this.$scope),
        $rightView : $(".right-view", this.$scope),
        eventManager: _.extend({}, Backbone.Events)
    };

    var sessionActive = function() {
        $.ajax({
            type: "POST",
            url: "/session/update/",
            dataType: 'json',
            data: {
                module : 3,
                usr : AdminApp.$scope.data("usr")
            },
            error: function(){
                window.setTimeout(function() {sessionActive();}, 10000);
            },
            timeout: function(){
                window.setTimeout(function() {sessionActive();}, 10000);
            },
            success: function(data){
                if(data) {
                    manageSession(data);
                    var t = 120000;
                    if(data.apps && parseInt(data.apps)>1) {
                        t = Math.round((Math.sqrt(parseInt(data.apps)-1) * 1.3 * 120000));
                    }
                    window.setTimeout(function() {sessionActive();}, t);
                }
            }
        })
    };

    var initialize = function (options) {
        if (false === "wsurl" in options) {
            throw "You must define a websocket url";
        }

        if (false === WSConnection.hasSession()) {
            WSConnection.connect(options.wsurl);
        }

        AdminApp.LeftView = new LeftPanel({
            el: AdminApp.$leftView,
            eventManager: AdminApp.eventManager
        });

        AdminApp.RightView = new RightPanel({
            el: AdminApp.$rightView,
            eventManager: AdminApp.eventManager
        });

        AdminApp.LeftView.activeTree();
        AdminApp.LeftView.clickSelected();

        window.setTimeout(function() {sessionActive();}, 15000);
    };

    return {
        initialize: initialize
    };
});
