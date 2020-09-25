import * as Rx from 'rx';

const overrideHotkeys = (settings) => {
    // override existing keys
    return {
        volumeUpKey: function (event, player) {
            // disable existing one
            return false;
        },
        volumeDownKey: function (event, player) {
            // disable existing one
            return false;
        },
        rewindKey: function (event, player) {
            // disable existing one
            return false;
        },
        forwardKey: function (event, player) {
            // disable existing one
            return false;
        },
    }
};

const tapSequenceHotKey = (keyStream, eventKey) => {

    return keyStream
        .filter(function (e) {
            if (e.which === eventKey) {
                return true;
            }
            return false;
        })
        .buffer(function () {
            return keyStream.debounce(250);
        })
        .map(function (list) {
            return list.length;
        })
        .filter(function (x) {
            return x >= 1;
        });

}

const hotkeys = (player, settings) => {

    let keyStream = Rx.Observable.fromEvent(settings.$container.get(0), 'keyup');
    let rates = settings.playbackRates;

    // L key speed 1x 2x 3x ...
    tapSequenceHotKey(keyStream, 76)
        .subscribe(function (numclicks) {
            let rate = rates[numclicks - 1];
            if (rate !== undefined) {
                player.playbackRate(rate);
            }
        });

    let hotkeys = {
        rewindKey: {
            key: function (e) {
                // Backward Arrow Key
                return (!e.ctrlKey && e.which === 37);
            },
            handler: (player, options) => {
                player.rangeControlBar.setPreviousFrame(parseInt(settings.seekBackwardStep, 10) / 1000)
            }
        },
        forwardKey: {
            key: function (e) {
                // forward Arrow Key
                return (!e.ctrlKey && e.which === 39);
            },
            handler: (player, options) => {
                player.rangeControlBar.setNextFrame(parseInt(settings.seekForwardStep, 10) / 1000)
            }
        },
        rewindFrameKey: {
            key: function (e) {
                // Backward Arrow Key
                return (e.ctrlKey && e.which === 37);
            },
            handler: (player, options) => {
                player.rangeControlBar.setPreviousFrame()
            }
        },
        forwardFrameKey: {
            key: function (e) {
                // forward Arrow Key
                return (e.ctrlKey && e.which === 39);
            },
            handler: (player, options) => {
                player.rangeControlBar.setNextFrame()
            }
        },
        playOnlyKey: {
            key: function (e) {
                // L Key
                return (!e.ctrlKey && e.which === 76);
            },
            handler: (player, options) => {
                if (player.paused()) {
                    player.play();
                }
            }
        },
        pauseOnlyKey: {
            key: function (e) {
                // K Key
                return (e.which === 75);
            },
            handler: (player, options) => {
                if (!player.paused()) {
                    player.pause();
                }
            }
        },
        frameBackward: {
            key: function (e) {
                // < Key
                return (e.which === 188);
            },
            handler: function (player, options) {
                player.rangeControlBar.setPreviousFrame()
            }
        },
        frameForward: {
            key: function (e) {
                // MAJ + < = > Key
                return (e.which === 190);
            },
            handler: function (player, options) {
                player.rangeControlBar.setNextFrame()
            }
        },
        moveDownRange: {
            key: function (e) {
                // K Key
                return (e.which === 40);
            },
            handler: (player, options) => {
                player.rangeCollection.setActiveRange('down');
            }
        },
        moveUpRange: {
            key: function (e) {
                // K Key
                return (e.which === 38);
            },
            handler: (player, options) => {
                player.rangeCollection.setActiveRange('up');
            }
        },
        entryCuePoint: {
            key: function (e) {
                // I Key
                return (!e.shiftKey && e.which === 73);
            },
            handler: function (player, options) {
                player.rangeStream.onNext({
                    action: 'update',
                    range: player.rangeControlBar.setStartPositon()
                });
            }
        },
        endCuePoint: {
            key: function (e) {
                // O Key
                return (!e.shiftKey && e.which === 79);
            },
            handler: function (player, options) {
                player.rangeStream.onNext({
                    action: 'update',
                    range: player.rangeControlBar.setEndPosition()
                });
            }
        },
        PlayAtEntryCuePoint: {
            key: function (e) {
                // I Key
                return (e.shiftKey && e.which === 73);
            },
            handler: function (player, options) {
                if (!player.paused()) {
                    player.pause();
                }
                player.currentTime(player.rangeControlBar.getStartPosition())
            }
        },
        PlayAtEndCuePoint: {
            key: function (e) {
                // O Key
                return (e.shiftKey && e.which === 79);
            },
            handler: function (player, options) {
                if (!player.paused()) {
                    player.pause();
                }
                player.currentTime(player.rangeControlBar.getEndPosition())
            }
        },
        toggleLoop: {
            key: function (e) {
                // ctrl+ L Key
                return (e.ctrlKey && e.which === 76);
            },
            handler: function (player, options) {
                player.rangeControlBar.toggleLoop();
            }
        },
        addRange: {
            key: function (e) {
                // ctrl + N or  shift + "+"
                return (e.ctrlKey && e.which === 78 || e.shiftKey && e.which === 107);
            },
            handler: (player, options) => {
                player.rangeControlBar.setNextFrame(parseInt(settings.seekForwardStep, 10) / 1000)
                let newRange = player.rangeCollection.addRange({});
                player.rangeStream.onNext({
                    action: 'create',
                    range: newRange
                })

            }
        },
        deleteRange: {
            key: function (e) {
                // MAJ+SUPPR Key
                return (e.shiftKey && e.which === 46);
            },
            handler: function (player, options) {
                player.rangeStream.onNext({
                    action: 'remove'
                });
            }
        }
    }

    return hotkeys;
}

export {overrideHotkeys, hotkeys};
