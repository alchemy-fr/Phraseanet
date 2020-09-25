import Flash from 'videojs-flash';
import FieldCollection from './../recordEditor/models/fieldCollection';

const videoRangeCapture = (services, datas, activeTab = false) => {
    const {configService, localeService, appEvents} = services;
    let $container = null;
    let videojs = {};
    let initData = {};
    let options = {
        playbackRates: [],
        fluid: true
    };
    let rangeCapture;
    const initialize = (params) => {
        $container = params.$container;
        initData = params.data;
        let aspectRatio = configService.get('resource.aspectRatio');


        if (configService.get('resource.aspectRatio') !== null) {
            options.aspectRatio = configService.get('resource.aspectRatio');
        }

        if (configService.get('resource.playbackRates') !== null) {
            options.playbackRates = configService.get('resource.playbackRates');
        }

        if (initData.videoEditorConfig !== null) {
            options.seekBackwardStep = initData.videoEditorConfig.seekBackwardStep;
            options.seekForwardStep = initData.videoEditorConfig.seekForwardStep;
            options.playbackRates = initData.videoEditorConfig.playbackRates === undefined ? [1, 2, 3] : initData.videoEditorConfig.playbackRates;
            options.vttFieldValue = false;
            options.ChapterVttFieldName = initData.videoEditorConfig.ChapterVttFieldName === undefined ? false : initData.videoEditorConfig.ChapterVttFieldName;
        }

        options.techOrder = ['html5', 'flash'];
        options.autoplay = false;
        options.recordId = initData.recordId;
        options.record = initData.records[0];
        options.databoxId = initData.databoxId;
        options.translations = initData.translations;
        options.services = params.services;
        options.preferences = initData.preferences;

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

        require.ensure([], () => {
            // load videoJs lib
            //require('../../videoEditor/style/main.scss');
            rangeCapture = require('../../videoEditor/rangeCapture').default;
            let rangeCaptureInstance = rangeCapture(services);
            rangeCaptureInstance.initialize(params, options);
            //render(initData);
        });

    }

    return {initialize}
}

export default videoRangeCapture;
