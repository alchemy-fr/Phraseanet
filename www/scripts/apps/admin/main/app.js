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

    // pull notification is called from menu bar

    // window.pullNotifications = function (){
    //     $.ajax({
    //         type: "POST",
    //         url:  AdminApp.$scope.data("notif-url"),
    //         dataType: 'json',
    //         data: {
    //             module : 3,
    //             usr : AdminApp.$scope.data("usr")
    //         },
    //         error: function(){
    //             window.setTimeout("pullNotifications();", 10000);
    //         },
    //         timeout: function(){
    //             window.setTimeout("pullNotifications();", 10000);
    //         },
    //         success: function(data){
    //             if (data) {
    //                 if (data.status == 'disconnected' || data.status == 'session') {
    //                     self.location.replace(self.location.href);
    //                 }
    //             }
    //             var t = 120000;
    //             if (data.apps && parseInt(data.apps) > 1) {
    //                 t = Math.round((Math.sqrt(parseInt(data.apps)-1) * 1.3 * 120000));
    //             }
    //             window.setTimeout("pullNotifications();", t);
    //         }
    //     });
    // };
    window.enableForms = function (forms) {
        forms.bind('submit', function(event){
            var method = $(this).attr('method');
            var url = $(this).attr('action');
            var datas = $(this).serializeArray();

            if(!method) {
                method = 'GET';
            }
            $('#right-ajax').empty().addClass('loading');
            if(url) {
                $.ajax({
                    type: method,
                    url: url,
                    data: datas,
                    success: enableFormsCallback
                });
                return false;
            }
        });
    };

    window.enableFormsCallback = function (datas)
    {
        $('#right-ajax').removeClass('loading').html(datas);
        enableForms($('#right-ajax form:not(.no-ajax)'));

        $.each($('#right-ajax a:not(.no-ajax)'),function(i, el){
            enableLink($(el));
        });
        return;
    };

    window.enableLink = function (link) {
        console.log('enable link')
        $(link).bind('click',function(event){

            var dest = link.attr('href');

            if(dest && dest.indexOf('#') !== 0) {
                $('#right-ajax').empty().addClass('loading').parent().show();

                $.get(dest, function(data) {
                    enableFormsCallback(data);
                });
                return false;
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

//        window.setTimeout(function() {pullNotifications();}, 15000);
    };

    return {
        create: create,
        initialize: initialize
    };
});
