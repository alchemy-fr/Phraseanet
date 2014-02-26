define([
    "underscore",
    "backbone"
], function (_, Backbone) {
    var instance;

    function init(url) {
        var activeSession = null;

        return _.extend({
            connect: function() {
                if (this.hasSession()) {
                    return;
                }
                var $this = this;
                // autobahn js is defined as a global object there is no way to load
                // it as a UMD module
                ab.connect(url, function (session) {
                    $this.setSession(session);
                    $this.trigger("ws:connect", activeSession);
                },
                function (code, reason) {
                    $this.trigger("ws:session-gone", code, reason);
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
            getSession: function() {
                return activeSession;
            },
            setSession: function(session) {
                activeSession = session;
            },
            subscribe: function(topic, callback) {
                if (false === this.hasSession()) {
                    this.on("ws:connect", function(session) {
                        session.subscribe(topic, callback);
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
    }

    return {
        getInstance: function(url) {
            if (!instance) {
                instance = init(url);
            }
            return instance;
        }
    };
});
