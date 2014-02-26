define([
    "underscore"
], function (_) {
    return function (options) {
        if (!"url" in options) {
            throw "You must set the websocket 'url'"
        }
        if (!"topic" in options) {
            throw "You must set the websocket 'topic'"
        }
        if (!"eventAggregator" in options) {
            throw "You must set an event aggregator"
        }

        var eventAggregator = options.eventAggregator;

        return {
            run: function() {
                // autobahn js is defined as a global object there is no way to load
                // it as a UMD module
                ab.connect(options.url, function (session) {
                    eventAggregator.trigger("ws:connect", session);
                    session.subscribe(options.topic, function (topic, msg) {
                            // double encoded string
                            var msg = JSON.parse(JSON.parse(msg));
                            eventAggregator.trigger("ws:"+msg.event, msg, session);
                        }
                    );
                },
                function (code, reason) {
                    eventAggregator.trigger("ws:session-gone", code,reason);
                });
            }
        }
    }
});
