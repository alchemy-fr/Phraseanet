define([
    "underscore",
    "backbone"
], function (_, Backbone) {
    var activeSession = null;

    return _.extend({
        connect: function(url) {
            if (this.isConnected()) {
                throw "Connection is already active";
            }
            var that = this;
            // autobahn js is defined as a global object there is no way to load
            // it as a UMD module
            ab.connect(url, function (session) {
                activeSession = session;
                that.trigger("ws:connect", activeSession);
            },
            function (code, reason) {
                that.trigger("ws:session-gone", code, reason);
            });
        },
        close: function() {
            if (false === this.isConnected()) {
                throw "Not connected to websocket";
            }
            activeSession.close();
            activeSession = null;
            this.trigger("ws:session-close");
        },
        isConnected: function() {
            return activeSession !== null;
        },
        subscribe: function(topic, callback) {
            if (false === this.isConnected()) {
                this.on("ws:connect", function(session) {
                    session.subscribe(topic, callback);
                    this.trigger("ws:session-subscribe", topic);
                });
                return;
            }
            activeSession.subscribe(topic, callback);
            this.trigger("ws:session-subscribe", topic);
        },
        unsubscribe: function(topic, callback) {
            if (false === this.isConnected()) {
                return;
            }
            activeSession.unsubscribe(topic, callback);
            this.trigger("ws:session-unsubscribe", topic);
        }
    }, Backbone.Events);
});
