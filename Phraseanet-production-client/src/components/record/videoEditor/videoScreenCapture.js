import $ from 'jquery';
import dialog from './../../../phraseanet-common/components/dialog';
import ScreenCapture from '../../videoEditor/screenCapture';

const videoScreenCapture = (services, datas, activeTab = false) => {
    const {configService, localeService, appEvents} = services;
    const url = configService.get('baseUrl');
    const initialize = (params) => {
        let {$container, data} = params;
        var ThumbEditor = new ScreenCapture('thumb_video', 'thumb_canvas', {
            altCanvas: $('#alt_canvas_container .alt_canvas')
        });

        if (ThumbEditor.isSupported()) {

            var $gridContainer = $('#grid', $container);

            $gridContainer.on('mousedown', '.grid-wrapper', function () {
                $('.selected', $gridContainer).removeClass('selected');
                $(this).addClass('selected');

                $container.find('.canvas-wrap').css('max-height','210px').css('width','auto');
                $container.find('#thumb_canvas').removeAttr('style').removeAttr('height');

                var $self = this;
                var selectedScreenId = $self.getAttribute('id').split('_').pop();
                var screenshots = ThumbEditor.store.get(selectedScreenId);

                ThumbEditor.copy(screenshots.getDataURI(), screenshots.getAltScreenShots());
            });

            $gridContainer.on('mouseup', '.grid-wrapper', function () {
                if ($container.find('#thumb_canvas').height() >= 210) {
                    $container.find('#thumb_canvas').css('height', '210px');
                }

            });

            $container.on('click', '#thumb_download_button', function() {
                downloadThumbnail($gridContainer.find('.selected'), $container);
            });

            $container.on('click', '#thumb_delete_button', function () {
                deleteThumbnail($gridContainer.find('.selected'), $container, ThumbEditor);
            });

            $container.on('click', '.close_action_frame', function () {
                $(this).closest('.action_frame').hide();
            });


            $container.on('click', '#thumb_camera_button', function () {
                $('#videotools-spinner').removeClass('hidden');
                setTimeout(launch_thumb_camera_button, 10);
            });
            function launch_thumb_camera_button() {
                /** set current time on real video capture**/
                var realVideoCurrent = document.getElementById('thumb_video_A').currentTime;
                document.getElementById('thumb_video').currentTime = realVideoCurrent;

                $('#thumb_delete_button', $container).show();
                $('#thumb_download_button', $container).show();

                var screenshot = '';
                /**screenshot at the real currentTime**/
               function getScreenShot() {
                    var screenshot = ThumbEditor.screenshot();

                    if($container.find('#thumb_canvas').height() >= 210) {
                        $container.find('#thumb_canvas').css('height', '210px');
                        $container.find('#grid').css('min-height', '210px');
                    }
                    $container.find('.frame_canva').css('width', $container.find('#thumb_canvas').width());
                    $container.find('.canvas-wrap').css('overflow','hidden').css('width', $container.find('#thumb_canvas').width());

                    $('.selected', $gridContainer).removeClass('selected');
                    var grid_wrapper = document.createElement('div');
                    $(grid_wrapper)
                        .addClass('grid-wrapper')
                        .addClass('selected')
                        .attr('id', 'working_' + screenshot.getId())
                        .append('<div id="small_thumb_download_button"/>')
                        .append('<div id="small_thumb_delete_button"/>');
                    var img = $('<img />');
                    img.attr('src', screenshot.getDataURI())
                        .attr('alt', screenshot.getVideoTime())
                        .appendTo($(grid_wrapper));

                    var grid_item = document.createElement('div');
                    $(grid_item).addClass('grid-item').append($(grid_wrapper)).appendTo($gridContainer);
                    $('#videotools-spinner').addClass('hidden');
                }
                setTimeout(getScreenShot, 1000);
            };

            $container.on('mouseup', '#thumb_camera_button', function () {
                $container.find('#thumb_canvas').removeAttr('style');
            });

            $gridContainer.on('click', '#small_thumb_download_button', function () {
                downloadThumbnail($(this).parent(), $container);
                return false;
            });

            $gridContainer.on('click', '#small_thumb_delete_button', function () {
                deleteThumbnail($(this).parent(), $container, ThumbEditor);
                return false;
            });

            $('#thumb_canvas').on('tool_event', function () {
                var thumbnail = $('.selected', $gridContainer);

                if (thumbnail.length === 0) {
                    console.error('No image selected');

                    return;
                }

                thumbnail.attr('src', ThumbEditor.getCanvaImage());

            });
            $container.on('click', '#thumb_validate_button', function () {
                var thumbnail = $('.selected', $gridContainer);
                let content = '';
                if (thumbnail.length === 0) {
                    let confirmationDialog = dialog.create(services, {
                        size: 'Custom',
                        customWidth: 360,
                        customHeight: 160,
                        title: data.translations.alertTitle,
                        closeOnEscape: true
                    }, 3);

                    confirmationDialog.getDomElement().closest('.ui-dialog').addClass('screenCapture_validate_dialog');

                    content = $('<div />').css({
                        'text-align': 'center',
                        width: '100%',
                        'font-size': '14px'
                    }).append(data.translations.noImgSelected);
                    confirmationDialog.setContent(content);

                    return false;
                } else {

                    var buttons = {};

                    var record_id = $('input[name=record_id]').val();
                    var sbas_id = $('input[name=sbas_id]').val();

                    var selectedScreenId = thumbnail.attr('id').split('_').pop();
                    var screenshots = ThumbEditor.store.get(selectedScreenId);


                    let screenData = screenshots.getAltScreenShots();
                    let subDefs = [];

                    for (let i = 0; i < screenData.length; i++) {
                        subDefs.push({
                            name: screenData[i].name,
                            src: screenData[i].dataURI

                        });
                    }

                    buttons = [
                        {
                            text: localeService.t('cancel'),
                            click: function(){
                                $(this).dialog('close');
                            }
                        },
                        {
                            text: localeService.t('valider'),
                            click: function () {
                                let confirmDialog = dialog.get(2);
                                var buttonPanel = confirmDialog.getDomElement().closest('.ui-dialog').find('.ui-dialog-buttonpane');
                                var loadingDiv = buttonPanel.find('.info-div');

                                if (loadingDiv.length === 0) {
                                    loadingDiv = $('<div />').css({
                                        width: '120px',
                                        height: '40px',
                                        float: 'left',
                                        'line-height': '40px',
                                        'padding-left': '40px',
                                        'text-align': 'left',
                                        'background-position': 'left center'
                                    }).attr('class', 'info-div').prependTo(buttonPanel);
                                }

                                $.ajax({
                                    type: 'POST',
                                    url: `${url}prod/tools/thumb-extractor/apply/`,
                                    data: {
                                        sub_def: subDefs,
                                        record_id: record_id,
                                        sbas_id: sbas_id
                                    },
                                    beforeSend: function () {
                                        disableConfirmButton(confirmDialog);
                                        loadingDiv.empty().addClass('loading').append(data.translations.processing);
                                    },
                                    success: function (data) {
                                        loadingDiv.empty().removeClass('loading');

                                        if (data.success) {
                                            confirmDialog.close();
                                            dialog.get(1).close();
                                        } else {
                                            loadingDiv.append(content);
                                            enableConfirmButton(confirmDialog);
                                        }
                                    }
                                });
                            },
                        }
                    ];

                    // show confirm box, content is loaded here /prod/tools/thumb-extractor/confirm-box/
                    var validationDialog = dialog.create(services, {
                        size: 'Custom',
                        customWidth: 360,
                        customHeight: 285,
                        title: data.translations.thumbnailTitle,
                        cancelButton: true,
                        buttons: buttons
                    }, 2);

                    validationDialog.getDomElement().closest('.ui-dialog').addClass('screenCapture_validate_dialog')

                    var datas = {
                        image: $('.selected', $gridContainer).find('img').attr('src'),
                        sbas_id: sbas_id,
                        record_id: record_id
                    };

                    return $.ajax({
                        type: 'POST',
                        url: `${url}prod/tools/thumb-extractor/confirm-box/`,
                        data: datas,
                        success: function (data) {

                            if (data.error) {
                                content = $('<div />').css({
                                    'font-size': '16px',
                                    'text-align': 'center'
                                }).append(data.datas);
                                validationDialog.setContent(content);
                                disableConfirmButton(validationDialog);
                            } else {
                                validationDialog.setContent(data.datas);
                            }
                        }
                    });
                }
            });
        } else {
            // not supported
            $('#thumbExtractor').empty().append(localeService.t('browserFeatureSupport'));
        }
    }

    const downloadThumbnail = (element, $container) => {
        var imageInBase64 = element.find('img').attr('src');
        var mimeInfo = base64MimeType(imageInBase64);
        var ext = mimeInfo.split('/').pop();
        var filename = "preview" + element.find('img').attr('alt') + "." + ext;
        download(imageInBase64, filename, mimeInfo);

    }

    const deleteThumbnail = (element, $container, ThumbEditor) => {
        var imgWrapper = element;
        var id = imgWrapper.attr('id').split('_').pop();
        var previous = imgWrapper.parent().prev();
        var next = imgWrapper.parent().next();

        if (previous.length > 0) {
            previous.find('.grid-wrapper').trigger('mousedown');
            previous.find('.grid-wrapper').trigger('mouseup');
        } else if (next.length > 0) {
            next.find('.grid-wrapper').trigger('mousedown');
            next.find('.grid-wrapper').trigger('mouseup');
        } else {
            $('#thumb_delete_button', $container).hide();
            $('#thumb_download_button', $container).hide();
            $('#thumb_canvas').attr('width',0);
            $('#thumb_canvas').attr('height',0);
            $('.frame_canva').removeAttr('style');
        }

        imgWrapper.parent().remove();
        ThumbEditor.store.remove(id);
    }

    const disableConfirmButton = (dialog) => {
        dialog.getDomElement().closest('.ui-dialog').find('.ui-dialog-buttonpane button').filter(function () {
            return $(this).text() === localeService.t('valider');
        }).addClass('ui-state-disabled').attr('disabled', true);
    }


    const enableConfirmButton = (dialog) => {
        dialog.getDomElement().closest('.ui-dialog').find('.ui-dialog-buttonpane button').filter(function () {
            return $(this).text() === localeService.t('valider');
        }).removeClass('ui-state-disabled').attr('disabled', false);
    }

    const base64MimeType = (encoded) => {
        let result = null;
        if (typeof encoded !== 'string') {
            return result;
        }
        let mime = encoded.match(/data:([a-zA-Z0-9]+\/[a-zA-Z0-9-.+]+).*,.*/);
        if (mime && mime.length) {
            result = mime[1];
        }
        return result;
    }

    return {
        initialize
    }
}

export default videoScreenCapture;
