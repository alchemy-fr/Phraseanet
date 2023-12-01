import _ from 'underscore';
import $ from 'jquery';
import rangePlugin from './rangePlugin/index';
let hotkeys = require('videojs-hotkeys');
import videojs from 'video.js';
import FieldCollection from "../recordEditor/models/fieldCollection";
// require('video.js').default;

const videoPreviewPlayer = (services, datas, activeTab = false) => {
    const {configService, localeService, appEvents} = services;
    let $container = null;
    let initData = {};
    let options = {};
    let defaultOptions = {
        playbackRates: [],
        fluid: true,
        controlBar: {
            muteToggle: false
        },
        baseUrl: configService.get('baseUrl')
    };
    let videoPlayer;
    const initialize = (params, userOptions) => {
        //{$container} = params;
        $container = params.$container;
        initData = params.data;

        options = _.extend(defaultOptions, userOptions, {$container: $container});

        options.techOrder = ['html5', 'flash'];
        options.autoplay = false;
        options.recordId = initData.recordId;
        options.record = initData.record;
        options.databoxId = initData.databoxId;
        // options.translations = initData.translations;
        // options.services = params.services;
        options.preferences = initData.preferences;

        if (initData.videoEditorConfig !== null) {
            options.seekBackwardStep = initData.videoEditorConfig.seekBackwardStep;
            options.seekForwardStep = initData.videoEditorConfig.seekForwardStep;
            options.playbackRates = initData.videoEditorConfig.playbackRates === undefined ? [1, 2, 3] : initData.videoEditorConfig.playbackRates;
            options.vttFieldValue = false;
            options.ChapterVttFieldName = initData.videoEditorConfig.ChapterVttFieldName === undefined ? false : initData.videoEditorConfig.ChapterVttFieldName;
        }

        // get default videoTextTrack value
        if (options.ChapterVttFieldName !== false) {
            var fieldCollection = new FieldCollection(initData.T_fields);
            let vttField = fieldCollection.getFieldByName(options.ChapterVttFieldName);
            if (vttField !== false) {
                if(vttField._value.VideoTextTrackChapters != undefined) {
                    options.vttFieldValue = vttField._value.VideoTextTrackChapters[0];
                }
                options.meta_struct_id = vttField.id;
            }
        }

        dispose();
        render(initData);
    };

    const render = (initData) => {
        let record = initData.record;
        if (record.type !== 'video') {
            return;
        }
        options.frameRates = {};
        options.ratios = {};
        let generateSourcesTpl = (record) => {
            let recordSources = [];
            _.each(record.sources, (s, i) => {
                recordSources.push(`<source src="${s.src}" type="${s.type}" data-frame-rate="${s.framerate}">`)
                options.frameRates[s.src] = s.framerate;
                options.ratios[s.src] = s.ratio;
            });

            return recordSources.join(' ');
        };

        let sources = generateSourcesTpl(record);
        $container.append(
            `<video id="embed-video" class="embed-resource video-js vjs-default-skin vjs-big-play-centered" controls
               preload="none" width="100%" height="100%"  data-setup='{"language":"${localeService.getLocale()}"}'>${sources} </video>`);

        // window.videojs = videojs;
        videojs.addLanguage(localeService.getLocale(), localeService.getTranslations());
        videoPlayer = videojs('embed-video', options, () => {
        });
        //group video elements together
        videoPlayer.rangePlugin(options);
        $(videoPlayer.el_).children().not('.range-item-container').wrapAll('<div class="video-player-container"></div>');
        videoPlayer.ready(() => {
            let hotkeyOptions = _.extend({
                alwaysCaptureHotkeys: true,
                enableNumbers: false,
                enableVolumeScroll: false,
                volumeStep: 0.1,
                seekStep: 1,
                customKeys: videoPlayer.getRangeCaptureHotkeys()
            }, videoPlayer.getRangeCaptureOverridedHotkeys());

            videoPlayer.hotkeys(hotkeyOptions);
        });

    };

    const dispose = () => {
        try {
            if (videojs.getPlayers()['embed-video']) {
                delete videojs.getPlayers()['embed-video'];
            }
        } catch (e) {
        }
    };

    const getPlayer = () => {
        return videoPlayer;
    };

    return {initialize, getPlayer}
};

export default videoPreviewPlayer;
