/* eslint-disable quotes */
require('./style/main.scss');
import $ from 'jquery';
import * as Rx from 'rx';
import videojs from 'video.js';
import HotkeyModal from './hotkeysModal';
import HotkeysModalButton from './hotkeysModalButton';
import RangeBarCollection from './rangeBarCollection';
import RangeCollection from './rangeCollection';
import RangeControlBar from './rangeControlBar';
import {WebVTT} from 'videojs-vtt.js';
import {overrideHotkeys, hotkeys} from './hotkeys';
import RangeItemContainer from './rangeItemContainer';
import * as appCommons from './../../../phraseanet-common';

// import rangeControls from './oldControlBar';

const icons = `
<svg style="position: absolute; width: 0; height: 0;" width="0" height="0" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
<defs>
<symbol id="icon-loop-range" viewBox="0 0 30 30">
<title>loop-range</title>
<path class="path1" d="M25.707 9.92l-2.133 1.813h1.707c0.107 0 0.32 0.213 0.32 0.213v8.107c0 0.107-0.213 0.213-0.32 0.213h-11.093l-0.853 2.133h11.947c1.067 0 2.453-1.28 2.453-2.347v-8.107c0-1.067-1.067-1.92-2.027-2.027z"></path>
<path class="path2" d="M7.040 22.4l1.92-2.133h-2.24c-0.107 0-0.32-0.213-0.32-0.213v-8.107c0 0 0.213-0.213 0.32-0.213h11.627l0.853-2.133h-12.48c-1.173 0-2.453 1.28-2.453 2.347v8.107c0 1.067 1.28 2.347 2.453 2.347h0.32z"></path>
<path class="path3" d="M17.493 6.827l4.053 3.947-4.053 3.947z"></path>
<path class="path4" d="M14.933 24.96l-3.947-3.84 3.947-3.947z"></path>
</symbol>
<symbol id="icon-prev-forward-frame" viewBox="0 0 30 30">
<title>prev-forward-frame</title>
<path class="path1" d="M25.432 9.942l-9.554 9.554-3.457-3.457 9.554-9.554 3.457 3.457z"></path>
<path class="path2" d="M21.912 25.578l-9.554-9.554 3.457-3.457 9.554 9.554-3.457 3.457z"></path>
<path class="path3" d="M6.578 6.489h2.578v19.111h-2.578v-19.111z"></path>
</symbol>
<symbol id="icon-next-forward-frame" viewBox="0 0 30 30">
<title>next-forward-frame</title>
<path class="path1" d="M10.131 6.462l9.554 9.554-3.457 3.457-9.554-9.554 3.457-3.457z"></path>
<path class="path2" d="M6.611 22.018l9.554-9.554 3.457 3.457-9.554 9.554-3.457-3.457z"></path>
<path class="path3" d="M22.756 6.489h2.578v19.111h-2.578v-19.111z"></path>
</symbol>
<symbol id="icon-prev-frame" viewBox="0 0 30 30">
<title>prev-frame</title>
<path class="path1" d="M22.538 9.962l-9.554 9.554-3.457-3.457 9.554-9.554 3.457 3.457z"></path>
<path class="path2" d="M19.018 25.558l-9.554-9.554 3.457-3.457 9.554 9.554-3.457 3.457z"></path>
</symbol>
<symbol id="icon-next-frame" viewBox="0 0 30 30">
<title>next-frame</title>
<path class="path1" d="M12.984 6.441l9.554 9.554-3.457 3.457-9.554-9.554 3.457-3.457z"></path>
<path class="path2" d="M9.464 22.039l9.554-9.554 3.457 3.457-9.554 9.554-3.457-3.457z"></path>
</symbol>
<symbol id="icon-cue-start" viewBox="0 0 30 30">
<title>cue-start</title>
<path class="path1" d="M20.356 24.089v-15.733c0-0.533-0.356-0.889-0.889-0.889h-8c-0.444 0-0.889 0.356-0.889 0.889v5.067c0 0.533 0.267 1.156 0.622 1.511l8.622 9.422c0.267 0.356 0.533 0.267 0.533-0.267z"></path>
</symbol>
<symbol id="icon-cue-end" viewBox="0 0 30 30">
<title>cue-end</title>
<path class="path1" d="M10.578 24.089v-15.733c0-0.533 0.356-0.889 0.889-0.889h8c0.444 0 0.889 0.356 0.889 0.889v5.067c0 0.533-0.267 1.156-0.622 1.511l-8.622 9.422c-0.267 0.356-0.533 0.267-0.533-0.267z"></path>
</symbol>
<symbol id="icon-trash" viewBox="0 0 30 30">
<title>trash</title>
<path class="path1" d="M22.667 8.978h-3.822v-1.333c0-0.8-0.622-1.422-1.422-1.422h-2.756c-0.8 0-1.422 0.622-1.422 1.422v1.422h-3.822c-0.178 0-0.356 0.178-0.356 0.356v0.711c0 0.178 0.178 0.356 0.356 0.356h13.333c0.178 0 0.356-0.178 0.356-0.356v-0.711c-0.089-0.267-0.267-0.444-0.444-0.444zM14.667 8.978v0-1.422h2.756v1.422h-2.756z"></path>
<path class="path2" d="M21.778 11.111h-11.733c-0.267 0-0.356 0.089-0.356 0.356v14.133c0 0 0.089 0.267 0.356 0.267h11.733c0.267 0 0.533-0.089 0.533-0.356v-14.133c0-0.178-0.267-0.267-0.533-0.267zM13.156 23.378c0 0.178-0.178 0.356-0.356 0.356h-0.711c-0.178 0-0.356-0.178-0.356-0.356v-9.778c0-0.178 0.178-0.356 0.356-0.356h0.711c0.178 0 0.356 0.178 0.356 0.356v9.778zM16.711 23.378c0 0.178-0.178 0.356-0.356 0.356h-0.711c-0.178 0-0.356-0.178-0.356-0.356v-9.778c0-0.178 0.178-0.356 0.356-0.356h0.711c0.178 0 0.356 0.178 0.356 0.356v9.778zM20.178 23.378c0 0.178-0.178 0.356-0.356 0.356h-0.711c-0.178 0-0.356-0.178-0.356-0.356v-9.778c0-0.178 0.178-0.356 0.356-0.356h0.711c0.178 0 0.356 0.178 0.356 0.356v9.778z"></path>
</symbol>
</defs>
</svg>
`;

const defaults = {
    seekBackwardStep: 1000,
    seekForwardStep: 1000,
    align: 'top-left',
    class: '',
    content: 'This overlay will show up while the video is playing',
    debug: false,
    overlays: [{
        start: 'playing',
        end: 'paused'
    }]
};

const Component = videojs.getComponent('Component');

const plugin = function (options) {
    const settings = videojs.mergeOptions(defaults, options);
    this.looping = false;
    this.loopData = [];
    this.activeRange = {};
    this.activeRangeStream = new Rx.Subject(); //new Rx.Observable.ofObjectChanges(this.activeRange);
    this.rangeStream = new Rx.Subject();
    this.rangeBarCollection = this.controlBar.getChild('progressControl').getChild('seekBar').addChild('RangeBarCollection', settings);
    this.rangeControlBar = this.addChild('RangeControlBar', settings);
    this.rangeItemContainer = this.addChild('RangeItemContainer', settings);
    this.rangeCollection = this.rangeItemContainer.getChild('RangeCollection');

    this.hotkeysModalButton = this.addChild('HotkeysModalButton', settings);

    $(this.el()).prepend(icons);

    this.setEditorWidth = () => {
        let editorWidth = this.currentWidth();

        if (editorWidth < 672) {
            $(this.el()).addClass('vjs-mini-screen');
        } else {
            $(this.el()).removeClass('vjs-mini-screen');
        }
    }

    this.setEditorHeight = () => {
        // gather components sizes
        let editorHeight = this.currentHeight() + $(this.rangeControlBar.el()).height() + $(this.rangeCollection.el()).height();

        if (editorHeight > 0) {
            options.$container.height(editorHeight + 'px');
        }
    }
    // range actions:
    this.rangeStream.subscribe((params) => {
        params.handle = params.handle || false;
        console.log('RANGE EVENT ===========', params.action, '========>>>')
        switch (params.action) {
            case 'initialize':
                this.rangeCollection.update(params.range);
                break;
            case 'select':
            case 'change':
                params.range = this.shouldTakeSnapShot(params.range, false);
                this.activeRange = this.rangeCollection.update(params.range);

                this.activeRangeStream.onNext({
                    activeRange: this.activeRange
                });

                this.rangeBarCollection.refreshRangeSliderPosition(this.activeRange);
                this.rangeControlBar.refreshRangePosition(this.activeRange, params.handle);
                setTimeout(() => {
                    this.rangeControlBar.setRangePositonToBeginning(params.range);
                }, 300);
                break;
            // flow through update:
            case 'create':
            case 'update':
                params.range = this.shouldTakeSnapShot(params.range, false);
                this.activeRange = this.rangeCollection.update(params.range);

                this.activeRangeStream.onNext({
                    activeRange: this.activeRange
                });

                this.rangeBarCollection.refreshRangeSliderPosition(this.activeRange);
                this.rangeControlBar.refreshRangePosition(this.activeRange, params.handle);
                break;
            case 'remove':
                // if a range is specified remove it from collection:
                if (params.range !== undefined) {
                    this.rangeCollection.remove(params.range);
                    if (params.range.id === this.activeRange.id) {
                        // remove from controls components too if active:
                        this.rangeBarCollection.removeActiveRange();
                        this.rangeControlBar.removeActiveRange();
                    }
                } else {
                    this.rangeBarCollection.removeActiveRange();
                    this.rangeControlBar.removeActiveRange();
                    this.rangeCollection.remove(this.activeRange);
                }

                break;
            case 'drag-update':
                // if changes come from range bar
                this.rangeControlBar.refreshRangePosition(params.range, params.handle);
                this.rangeCollection.updatingByDragging(params.range);

                // setting currentTime may take some additionnal time,
                // so let's wait:
                setTimeout(() => {
                    if (params.handle === 'start') {
                        params.range = this.shouldTakeSnapShot(params.range, false);
                        this.rangeCollection.update(params.range);
                    }
                }, 900);
                break;
            case 'export-ranges':
                break;
            case 'export-vtt-ranges':
                this.rangeCollection.exportRangesData(params.data);
                break;
            case 'resize':
                this.setEditorWidth();
                break;
            case 'saveRangeCollectionPref': 
                this.saveRangeCollectionPref(params.data);
                break;
            case 'capture':
                // if a range is specified remove it from collection:
                if (params.range !== undefined) {
                    params.range = this.shouldTakeSnapShot(params.range, true);
                    this.rangeCollection.update(params.range);
                }
                break;
            default:
        }
        console.log('<<< =================== RANGE EVENT COMPLETE')

    });

    this.shouldTakeSnapShot = (range, atCurrentPosition) => {
        if(atCurrentPosition) {
            this.takeSnapshot(range);
            range.manualSnapShot = true;
            return range;
        }
        else if (Math.round(range.startPosition) == Math.round(this.currentTime()) && !range.manualSnapShot) {
            this.takeSnapshot(range);
            return range;
        } else {
            return range;
        }
    }

    this.takeSnapshot = (range) => {
        let video = this.el().querySelector('video');
        let canvas = document.createElement('canvas');
        let ratio = settings.ratios[this.cache_.src];
        canvas.width = 50 * ratio;
        canvas.height = 50;
        let context = canvas.getContext('2d');

        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        let dataURI = canvas.toDataURL('image/jpeg');

        range.image = {
            src: dataURI,
            ratio: settings.ratios[this.cache_.src],
            width: canvas.width,
            height: canvas.height
        }

        return range;
    }

    this.setVTT = () => {
        if (settings.vttFieldValue !== false) {
            // reset existing collection
            this.rangeCollection.reset([])

            // prefill chapters with vtt data
            let parser = new WebVTT.Parser(window,
                window.vttjs,
                WebVTT.StringDecoder());

            let errors = [];

            parser.oncue = (cue) => {

                // try to parse text:
                let parsedCue = false;
                try {
                    parsedCue = JSON.parse(cue.text || '{}');

                } catch (e) {
                    console.error('failed to parse cue text', e)
                }
                if (parsedCue === false) {
                    parsedCue = {
                        title: cue.text
                    }
                }

                let newRange = this.rangeCollection.addRange({
                    startPosition: cue.startTime,
                    endPosition: cue.endTime,
                    title: parsedCue.title,
                    image: {
                        src: parsedCue.image || ''
                    },
                    manualSnapShot: parsedCue.manualSnapShot

                });

                this.rangeStream.onNext({
                    action: 'initialize',
                    range: newRange
                })
            };

            parser.onparsingerror = function (error) {
                errors.push(error);
            };

            parser.parse(settings.vttFieldValue);
            if (errors.length > 0) {
                if (console.groupCollapsed) {
                    console.groupCollapsed(`Text Track parsing errors`);
                }
                errors.forEach((error) => console.error(error));
                if (console.groupEnd) {
                    console.groupEnd();
                }
            }

            parser.flush();
        }
    }

    this.ready(() => {

        /*resize video*/
        var videoChapterH = $('#rangeExtractor').height();
        $('#rangeExtractor .video-player-container').css('max-height', videoChapterH);
        $('#rangeExtractor .range-collection-container').css('height', videoChapterH - 100);
        $('#rangeExtractor .video-range-editor-container').css('max-height', videoChapterH).css('overflow','hidden');


        this.setVTT();
        // if we have to load existing chapters, let's trigger loadedmetadata:
        if (settings.vttFieldValue !== false) {
            var playPromise = null;
            if (this.paused()) {
                playPromise = this.play().then(() => {
                    this.pause();
                }).catch(error => {
                    });
            }
            if (!this.paused()) {
                if (playPromise !== undefined) {
                    playPromise.then(() => {
                        this.pause();
                    })
                    .catch(error => {
                     });
                }
            }
        }
    });

    this.one('loadedmetadata', () => {
        //this.setEditorHeight();
        this.setEditorWidth();
        if (settings.vttFieldValue !== false) {
            //this.currentTime(0);
        }
    });

    // ensure control bar is always visible by simulating user activity:
    this.on('timeupdate', () => {
        this.userActive(true);
    });

    // override existing hotkeys:
    this.getRangeCaptureOverridedHotkeys = () => overrideHotkeys(settings);

    // set new hotkeys
    this.getRangeCaptureHotkeys = () => hotkeys(this, settings);

    // init a default range once every components are ready:
    this.rangeCollection.initDefaultRange();

    this.saveRangeCollectionPref = (isChecked) => { 
        appCommons.userModule.setPref('overlapChapters', (isChecked ? '1' : '0')); 
    }
}
videojs.plugin('rangeCapturePlugin', plugin);
export default plugin;
