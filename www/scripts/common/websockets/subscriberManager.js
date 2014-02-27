define([
    "underscore",
    "backbone",
    "common/websockets/connection"
], function (_, Backbone, WSConnection) {
    var currentTopic = null;
    var callbackStack = [];

    var callbackHandler = function (topic, msg) {
        _.each(callbackStack, function(cb) {
            cb(topic, msg);
        });
    };

    var reset = function () {
        callbackStack = [];
        currentTopic = null;
    };

    return {
        'register': function (topic) {
            this.unregister();
            WSConnection.subscribe(topic, callbackHandler);
            currentTopic = topic;
        },
        'unregister': function () {
            if (currentTopic !== null) {
                WSConnection.unsubscribe(currentTopic);
                reset();
            }
        },
        'pushCallback': function (callback) {
            callbackStack.push(callback);
        }
    }
});
