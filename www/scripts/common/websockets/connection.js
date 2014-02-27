define([
    "underscore",
    "backbone"
], function (_, Backbone) {
    var activeSession = null;

    return _.extend({
        connect: function(url) {
            if (this.hasSession()) {
                throw "Connection is already active";
            }
            var that = this;
            // autobahn js is defined as a global object there is no way to load
            // it as a UMD module
            ab.connect(url, function (session) {
                that.setSession(session);
                that.trigger("ws:connect", activeSession);
            },
            function (code, reason) {
                that.trigger("ws:session-gone", code, reason);
            });
        },
        close: function() {
            if (false === this.hasSession()) {
                return;
            }
            this.getSession().close();
            this.setSession(null);
            this.trigger("ws:session-close");
        },
        hasSession: function() {
            return this.getSession() !== null;
        },
        subscribe: function(topic, callback) {
            if (false === this.hasSession()) {
                this.on("ws:connect", function(session) {
                    session.subscribe(topic, callback);
                    this.trigger("ws:session-subscribe", topic);
                });
                return;
            }
            this.getSession().subscribe(topic, callback);
            this.trigger("ws:session-subscribe", topic);
        },
        unsubscribe: function(topic, callback) {
            if (false === this.hasSession()) {
                return;
            }
            this.getSession().unsubscribe(topic, callback);
            this.trigger("ws:session-unsubscribe", topic);
        }
    }, Backbone.Events);
});
