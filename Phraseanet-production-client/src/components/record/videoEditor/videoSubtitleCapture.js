import $ from 'jquery';
const humane = require('humane-js');
;

const videoSubtitleCapture = (services, datas, activeTab = false) => {
    const {configService, localeService, appEvents} = services;
    const url = configService.get('baseUrl');
    const initialize = (params, userOptions) => {
        let {$container, data} = params;
        var initialData = data;
        var videoSource = "/embed/?url=/datafiles/" + initialData.databoxId + "/" + initialData.recordId + "/preview/%3Fetag";

        function loadVideo() {
            $('.video-subtitle-right .video-subtitle-wrapper').html('');
            if (initialData.records[0].sources.length > 0) {
                var prevWidth = initialData.records[0].sources[0].width;
                var prevHeight = initialData.records[0].sources[0].height;
                var prevRatio = initialData.records[0].sources[0].ratio;
                $('.video-subtitle-right .video-subtitle-wrapper').append("<iframe class='video-player' src=" + videoSource + " data-width=" + prevWidth + " data-height=" + prevHeight + " data-ratio=" + prevRatio + "  scrolling='no' marginheight='0' frameborder='0' allowfullscreen=''></iframe>");
                resizeVideoPreview();
            } else {
                $('.video-subtitle-right .video-subtitle-wrapper').append("<img  src='/assets/common/images/icons/substitution/video_webm.png'>");
            }

        }

        function resizeVideoPreview() {

            var $sel = $('.video-subtitle-wrapper');
            var $Iframe = $('.video-subtitle-wrapper iframe');
            // V is for "video" ; K is for "container" ; N is for "new"
            var VW = $Iframe.data('width');
            var VH = $Iframe.data('height');
            var KW = $('#prod-tool-box').width() / 2;
            var KH = $('.video-subtitle-left-inner').closest('#tool-tabs').height() - 100;

            var NW, NH;
            if ((NH = VH / VW * (NW = KW)) > KH) {
                // try to fit exact horizontally, adjust vertically
                // too bad... new height overflows container height
                NW = VW / VH * (NH = KH); // so fit exact vertically, adjust horizontally
            }
            //    (0, _jquery2.default)($Iframe).css('width', NW).css('height', NH);
            $($Iframe).attr('width', NW).css('width', NW);
            $($Iframe).attr('height', NH).css('height', NH);
        }

        loadVideo();
        setTimeout(function () {
            resizeVideoPreview()
        }, 2000);

        $('.subtitleEditortoggle').on('click', function (e) {
            resizeVideoPreview();
        });

        $container.on('click', '.add-subtitle-vtt', function (e) {
            e.preventDefault();
            addSubTitleVtt();
            setDiffTime();

        });
        let startVal = 0;
        let endVal = 0;
        let diffVal = 0;
        let leftHeight = 300;

        // Set height of left block
        leftHeight = $('.video-subtitle-left-inner').closest('#tool-tabs').height();
        $('.video-subtitle-left-inner').css('height', leftHeight - 147);
        $('.video-request-left-inner').css('height', leftHeight);
        $('.video-subtitle-right .video-subtitle-wrapper').css('height', leftHeight - 100);


        $('.endTime').on('keyup change', function (e) {
            setDefaultStartTime();
            endVal = stringToseconde($(this).val());
            startVal = stringToseconde($(this).closest('.video-subtitle-item').find('.startTime').val());
            diffVal = millisecondeToTime(endVal - startVal);
            $(this).closest('.video-subtitle-item').find('.showForTime').val(diffVal);
        });
        $('.startTime').on('keyup change', function (e) {
            setDefaultStartTime();
            startVal = stringToseconde($(this).val());
            endVal = stringToseconde($(this).closest('.video-subtitle-item').find('.endTime').val());
            diffVal = millisecondeToTime(endVal - startVal);
            $(this).closest('.video-subtitle-item').find('.showForTime').val(diffVal);
        });
        function setDefaultStartTime(e) {

            var DefaultStartT = $('.video-subtitle-item:last .endTime').val();
            DefaultStartT = stringToseconde(DefaultStartT) + 1;
            DefaultStartT = millisecondeToTime(DefaultStartT);

            var DefaultEndT = stringToseconde(DefaultStartT) + 2000;
            DefaultEndT = millisecondeToTime(DefaultEndT);

            $('#defaultStartValue').val(DefaultStartT);
            $('#defaultEndValue').val(DefaultEndT);

        }

        function setDiffTime(e) {
            $('.endTime').on('keyup change', function (e) {
                setDefaultStartTime();
                endVal = stringToseconde($(this).val());
                startVal = stringToseconde($(this).closest('.video-subtitle-item').find('.startTime').val());
                diffVal = millisecondeToTime(endVal - startVal);
                $(this).closest('.video-subtitle-item').find('.showForTime').val(diffVal);
            });
            $('.startTime').on('keyup change', function (e) {
                setDefaultStartTime();
                startVal = stringToseconde($(this).val());
                endVal = stringToseconde($(this).closest('.video-subtitle-item').find('.endTime').val());
                diffVal = millisecondeToTime(endVal - startVal);
                $(this).closest('.video-subtitle-item').find('.showForTime').val(diffVal);

            });
        }

        function stringToseconde(time) {
            let tt = time.split(":");
            let sec = tt[0] * 3600 + tt[1] * 60 + tt[2] * 1;
            return sec * 1000;
        }

        function millisecondeToTime(duration) {
            var milliseconds = parseInt((duration % 1000 / 100) * 100),
                seconds = parseInt((duration / 1000) % 60),
                minutes = parseInt((duration / (1000 * 60)) % 60),
                hours = parseInt((duration / (1000 * 60 * 60)) % 24);

            hours = (hours < 10) ? "0" + hours : hours;
            minutes = (minutes < 10) ? "0" + minutes : minutes;
            seconds = (seconds < 10) ? "0" + seconds : seconds;
            // if(isNaN(hours) && isNaN(minutes) && isNaN(seconds) && isNaN(milliseconds) ) {
            return hours + ":" + minutes + ":" + seconds + "." + milliseconds;
            //}

        }

        $container.on('click', '.remove-item', function (e) {
            e.preventDefault();
            if ($(this).closest('.editing').length > 0) {
                $(this).closest('.editing').remove();
            } else {
                $(this).closest('.video-subtitle-item').remove();
            }
        });

        $('#submit-subtitle').on('click', function (e) {
            e.preventDefault();
            buildCaptionVtt('save');
        });

        $('#copy-subtitle').on('click', function (event) {
            event.preventDefault();
            buildCaptionVtt('copy');
        });

        function buildCaptionVtt(btn) {
            try {
                let allData = $('#video-subtitle-list').serializeArray();
                allData = JSON.parse(JSON.stringify(allData));
                allData = JSON.parse(JSON.stringify(allData));
                let metaStructId = $('#metaStructId').val();

                let countSubtitle = $('.video-subtitle-item').length;
                if (allData) {
                    var i = 0;
                    var j = 0;
                    var captionText = "WEBVTT - with cue identifier\n\n";
                    while (i <= countSubtitle * 3) {
                        j= j +1;
                        captionText += j + "\n" + allData[i].value + " --> " + allData[i + 1].value + "\n" + allData[i + 2].value + "\n\n";
                        i = i + 3;
                        if (i == (countSubtitle * 3) - 3) {
                            $('#record-vtt').val(captionText);
                            console.log(captionText);
                            if (btn == 'save') {
                                //send data
                                $.ajax({
                                    type: 'POST',
                                    url: url + 'prod/tools/metadata/save/',
                                    dataType: 'json',
                                    data: {
                                        databox_id: data.databoxId,
                                        record_id: data.recordId,
                                        meta_struct_id: metaStructId,
                                        value: captionText
                                    },
                                    success: function success(data) {
                                        if (!data.success) {
                                            humane.error(localeService.t('prod:videoeditor:subtitletab:messsage:: error'));
                                        } else {
                                            humane.info(localeService.t('prod:videoeditor:subtitletab:messsage:: success'));
                                            loadVideo();
                                        }
                                    }
                                });
                            }
                            if (btn == 'copy') {
                                return copyElContentClipboard('record-vtt');
                            }
                        }
                    }
                    ;
                }

            } catch (err) {
                return;
            }
        }

        var copyElContentClipboard = function copyElContentClipboard(elId) {
            var copyEl = document.getElementById(elId);
            copyEl.select();
            try {
                var successful = document.execCommand('copy');
                var msg = successful ? 'successful' : 'unsuccessful';
            } catch (err) {
                console.log('unable to copy');
            }
        };


        const addSubTitleVtt = () => {
            let countSubtitle = $('.video-subtitle-item').length;
            if ($('.alert-wrapper').length) {
                $('.alert-wrapper').remove();
            }
            if (countSubtitle > 1) {
                setDefaultStartTime();
            }
            let item = $('#default-item').html();
            $('.fields-wrapper').append(item);
            $('.video-subtitle-item:last .time').attr('pattern', '[0-9][0-9]:[0-9][0-9]:[0-9][0-9].[0-9]{3}$');
            $('.video-subtitle-item:last .startTime').attr('name', 'startTime' + countSubtitle).addClass('startTime' + countSubtitle);
            $('.video-subtitle-item:last .endTime').attr('name', 'endTime' + countSubtitle).addClass('endTime' + countSubtitle);
            $('.video-subtitle-item:last .number').html(countSubtitle);
            if (countSubtitle > 1) {
                $('.video-subtitle-item:last .startTime').val($('#defaultStartValue').val());
                $('.video-subtitle-item:last .endTime').val($('#defaultEndValue').val());

            }
            //setDiffTime();
        };


        // Edit subtitle
        var fieldvalue = '';
        var ResValue = '';
        var captionValue = '';
        var captionLength = '';
        var timeValue = '';

        //Show default caption to edit
        fieldvalue = $('#caption_' + $('#metaStructId').val()).val();
        editCaptionByLanguage(fieldvalue);


        $('#metaStructId').on('keyup change', function (e) {
            fieldvalue = $('#caption_' + $(this).val()).val();
            editCaptionByLanguage(fieldvalue);
            $('.editing > .caption-label').click(function (e) {
                $(this).next('.video-subtitle-item').toggleClass('active');
                $(this).toggleClass('caption_active');
            })
        });

        $('.editing > .caption-label').click(function (e) {
            $(this).next('.video-subtitle-item').toggleClass('active');
            $(this).toggleClass('caption_active');
        })

        function editCaptionByLanguage(fieldvalue) {
            $('.fields-wrapper').html('');
            var item = $('#default-item').html();

            if (fieldvalue != '' && fieldvalue!= undefined) {
                var withCueId = false;
                //var fieldType = fieldvalue.split("WEBVTT");
                var fieldType = fieldvalue.split("\n\n");
                if (fieldType[0] === 'WEBVTT - with cue identifier') {
                    // with cue
                    ResValue = fieldvalue.split("WEBVTT - with cue identifier\n\n");
                    captionValue = ResValue[1].split("\n\n");
                    captionLength = captionValue.length;
                    console.log(captionValue);
                    for (var i = 0; i <= captionLength - 1; i++) {

                        // Regex blank line
                        var ResValueItem = captionValue[i].replace(/\n\r/g, "\n")
                            .replace(/\r/g, "\n")
                            .split(/\n{2,}/g);

                        var captionValueItem = ResValueItem[0].split("\n");
                        var captionNumber = captionValueItem[0];
                        var timing = captionValueItem[1];
                        var text1 = captionValueItem.slice(2);
                        if (text1.length > 1) {
                            var text = text1.join('\n');
                        } else {
                            var text = text1;
                        }

                        var timeValue = timing.split(" --> ");
                        var startTimeLabel = timeValue[0];
                        $('.fields-wrapper').append('<div class="item_' + i + ' editing"></div>')
                        $('.fields-wrapper .item_' + i + '').append('<p class="caption-label"><span class="number">' + captionNumber + '</span><span class="start-label"></span> --> <span class="end-label"></span><span class="duration"></span><span class="text-label"></span></p>');
                        $('.fields-wrapper .item_' + i + '').append(item);
                        $('.item_' + i + ' .video-subtitle-item ').find('.number').remove();

                        //Re-Build StartTime
                        $('.item_' + i + ' .video-subtitle-item ').closest('.editing').find('.start-label').text(startTimeLabel);
                        $('.item_' + i + ' .video-subtitle-item ').find('.startTime').val(startTimeLabel);

                        startVal = stringToseconde(timeValue[0]);
                        //Re-Build EndTime
                        timeValue = timeValue [1].split("\n")
                        $('.item_' + i + ' .video-subtitle-item ').closest('.editing').find('.end-label').text(timeValue[0]);
                        $('.item_' + i + ' .video-subtitle-item ').find('.endTime').val(timeValue[0]);
                        endVal = stringToseconde(timeValue[0]);

                        //Re-build Duration
                        diffVal = millisecondeToTime(endVal - startVal);
                        $('.item_' + i + ' .video-subtitle-item ').closest('.editing').find('.duration').text(diffVal);
                        $('.item_' + i + ' .video-subtitle-item ').find('.showForTime').val(diffVal);

                        //Re-build caption text
                        var textTrimed = text && text.length > length ? text.substring(0, 30) + '...' : text;
                        if (timeValue[1] != '') {
                            $('.item_' + i + ' .video-subtitle-item ').closest('.editing').find('.text-label').text(textTrimed);
                            $('.item_' + i + ' .video-subtitle-item ').find('.captionText').val(text);
                        }
                        //end with cue number
                    }

                } else {
                    ResValue = fieldvalue.split("WEBVTT\n\n");
                    captionValue = ResValue[1].split("\n\n");
                    captionLength = captionValue.length - 1;

                    var captionNumber;
                    for (var i = 0; i < captionLength; i++) {
                        captionNumber = i + 1;
                        timeValue = captionValue[i].split(" --> ");
                        var startTimeLabel = timeValue[0];
                        $('.fields-wrapper').append('<div class="item_' + i + ' editing"></div>')
                        $('.fields-wrapper .item_' + i + '').append('<p class="caption-label"><span class="number">' + captionNumber + '</span><span class="start-label"></span> --> <span class="end-label"></span><span class="duration"></span><span class="text-label"></span></p>');
                        $('.fields-wrapper .item_' + i + '').append(item);
                        $('.item_' + i + ' .video-subtitle-item ').find('.number').remove();

                        //Re-Build StartTime
                        $('.item_' + i + ' .video-subtitle-item ').closest('.editing').find('.start-label').text(startTimeLabel);
                        $('.item_' + i + ' .video-subtitle-item ').find('.startTime').val(startTimeLabel);

                        startVal = stringToseconde(timeValue[0]);
                        //Re-Build EndTime
                        timeValue = timeValue [1].split("\n")
                        $('.item_' + i + ' .video-subtitle-item ').closest('.editing').find('.end-label').text(timeValue[0]);
                        $('.item_' + i + ' .video-subtitle-item ').find('.endTime').val(timeValue[0]);
                        endVal = stringToseconde(timeValue[0]);

                        //Re-build Duration
                        diffVal = millisecondeToTime(endVal - startVal);
                        $('.item_' + i + ' .video-subtitle-item ').closest('.editing').find('.duration').text(diffVal);
                        $('.item_' + i + ' .video-subtitle-item ').find('.showForTime').val(diffVal);

                        //Re-build caption text
                        var textTrimed = timeValue[1] && timeValue[1].length > length ? timeValue[1].substring(0, 30) + '...' : timeValue[1];
                        if (timeValue[1] != '') {
                            var text = timeValue.slice(1);
                            text = text.join('\n');
                            $('.item_' + i + ' .video-subtitle-item ').closest('.editing').find('.text-label').text(textTrimed);
                            $('.item_' + i + ' .video-subtitle-item ').find('.captionText').val(text);
                        }

                    }
                }


                setDiffTime();
            } else {
                var errorMsg = $('#no_caption').val();
                $('.fields-wrapper').append('<p class="text-center alert-wrapper"><span class="alert alert-info">' + errorMsg + '</span></p>');
            }
        }

        //Subtitle Request Tab
        $('#submit-subtitle-request').on('click', function (e) {
            e.preventDefault();
            try {
                var requestData = $('#video-subtitle-request').serializeArray();
                requestData = JSON.parse(JSON.stringify(requestData));
                console.log(requestData)

            } catch (err) {
                return;
            }
        });
    }

    /*    const render = (initData) => {
     let record = initData.records[0];
     if (record.type !== 'video') {
     return;
     }
     options.frameRates = {};
     options.ratios = {};
     const coverUrl = '';
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
     $('.video-subtitle-right .video-subtitle-wrapper').html('');
     $('.video-subtitle-right .video-subtitle-wrapper').append(
     `<video id="embed-video" class="thumb_video embed-resource video-js vjs-default-skin vjs-big-play-centered" data-ratio="{{ prevRatio }}" controls
     preload="none" width="100%" height="100%" poster="${coverUrl}" data-setup='{"language":"${localeService.getLocale()}"}'>${sources}
     <track kind="captions" src=${$('#record-vtt').val()} srclang="en" label="English" default>
     </video>`);
     };*/

    return {
        initialize
    }
}


export default videoSubtitleCapture;
