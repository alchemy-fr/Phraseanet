import $ from 'jquery';
import Flash from 'videojs-flash';

const videoEditor = (services) => {
    const {configService, localeService, recordEditorEvents} = services;
    let $container = null;
    let parentOptions = {};
    let data;
    let rangeCapture;
    let rangeCaptureInstance;
    let options = {
        playbackRates: [],
        fluid: true
    };
    const initialize = (params) => {
        let initWith = {$container, parentOptions, data} = params;

        if (data.videoEditorConfig !== null) {
            options.seekBackwardStep = data.videoEditorConfig.seekBackwardStep;
            options.seekForwardStep = data.videoEditorConfig.seekForwardStep;
            options.playbackRates = data.videoEditorConfig.playbackRates === undefined ? [1, 2, 3] : data.videoEditorConfig.playbackRates;
            options.vttFieldValue = false;
            options.ChapterVttFieldName = data.videoEditorConfig.ChapterVttFieldName === undefined ? false : data.videoEditorConfig.ChapterVttFieldName;
        }

        options.techOrder = ['html5', 'flash'];
        $container.addClass('video-range-editor-container');

        // get default videoTextTrack value
        if (options.ChapterVttFieldName !== false) {
            let vttField = parentOptions.fieldCollection.getFieldByName(options.ChapterVttFieldName);
            if (vttField !== false) {
                options.vttFieldValue = vttField._value
            }
        }

        require.ensure([], () => {

            // load videoJs lib
            rangeCapture = require('../../../videoEditor/rangeCapture').default;
            rangeCaptureInstance = rangeCapture(services);
            rangeCaptureInstance.initialize(params, options);

            // proxy resize event to rangeStream
            recordEditorEvents.listenAll({
                'recordEditor.uiResize': () => {
                    rangeCaptureInstance.getPlayer().rangeStream.onNext({action: 'resize'})
                }
            })


            rangeCaptureInstance.getPlayer().rangeStream.subscribe((params) => {
                switch (params.action) {
                    case 'export-vtt-ranges':
                        if (options.ChapterVttFieldName !== false) {

                            let presets = {
                                fields: {}
                            };
                            presets.fields[options.ChapterVttFieldName] = [params.data];
                            recordEditorEvents.emit('recordEditor.addPresetValuesFromDataSource', {
                                data: presets
                            });
                        }
                        break;
                    default:
                }
            });
        });
    };

    return {initialize};
};
export default videoEditor;
