import $ from 'jquery';
import pym from 'pym.js';
import videoEditor from './videoEditor';
//require('jquery-ui');

const preview = services => {
    const { configService, localeService, recordEditorEvents } = services;
    let $container = null;
    let parentOptions = {};
    let activeThumbnailFrame = false;
    let lastRecordIndex = false;

    recordEditorEvents.listenAll({
        // @TODO debounce
        'recordEditor.uiResize': onResize,
        'recordSelection.changed': onSelectionChange,
        'recordEditor.onSelectRecord': renderPreview,
        'recordEditor.tabChange': tabChanged
    });

    const initialize = options => {
        let initWith = ({ $container, parentOptions } = options);
    };

    function onResize() {
        var selected = $('#EDIT_FILM2 .diapo.selected');

        if (selected.length !== 1) {
            return false;
        }

        var id = selected.attr('id').split('_').pop();

        var zoomable = $('img.record.zoomable', $container.parent());

        if (zoomable.length > 0 && zoomable.hasClass('zoomed')) {
            return false;
        }

        var h = parseInt(
            $($container.children()).attr('data-original-height'),
            10
        );
        var w = parseInt(
            $($container.children()).attr('data-original-width'),
            10
        );

        var t = 0;
        var de = 0;

        var margX = 20;
        var margY = 20;

        if ($('img.record.record_audio', $container).length > 0) {
            margY = 100;
            de = 60;
        }
        let containerWidth = $container.parent().width();
        let containerHeight = $container.parent().height();

        //  if(datas.doctype != 'flash')
        //  {
        var ratioP = w / h;
        var ratioD = containerWidth / containerHeight;

        if (ratioD > ratioP) {
            // je regle la hauteur d'abord
            if (parseInt(h, 10) + margY > containerHeight) {
                h = Math.round(containerHeight - margY);
                w = Math.round(h * ratioP);
            }
        } else {
            if (parseInt(w, 10) + margX > containerWidth) {
                w = Math.round(containerWidth - margX);
                h = Math.round(w / ratioP);
            }
        }
        t = Math.round((containerHeight - h - de) / 2);
        var l = Math.round((containerWidth - w) / 2);
        $('.record', $container.parent())
            .css({
                width: w,
                height: h,
                top: t,
                left: l,
                margin: '0 auto',
                display: 'block'
            })
            .attr('width', w)
            .attr('height', h);
    }

    function tabChanged(params) {
        if (params.tab === '#TH_Opreview') {
            //redraw preview
            var selected = $('#EDIT_FILM2 .diapo.selected');
            if (selected.length !== 1) {
                return false;
            }
            var id = selected.attr('id').split('_').pop();
            renderPreview({
                recordIndex: id
            });
        }
    }

    function renderPreview(params) {
        let { recordIndex } = params;

        lastRecordIndex = recordIndex;

        let currentRecord = parentOptions.recordCollection.getRecordByIndex(
            recordIndex
        );

        $container.empty();

        if (currentRecord === false) {
            return false;
        }

        switch (currentRecord.type) {
            case 'video':
            case 'audio':
            case 'document':
                let customId = 'phraseanet-embed-editor-frame';
                let $template = $(currentRecord.template);
                $template.attr('id', customId);

                $container.append($template.get(0));
                activeThumbnailFrame = new pym.Parent(
                    customId,
                    currentRecord.data.preview.url
                );
                activeThumbnailFrame.iframe.setAttribute('allowfullscreen', '');
                break;
            case 'image':
            default:
                $container.append(currentRecord.template);
                onResize();
        }

        if ($('img.PREVIEW_PIC.zoomable').length > 0) {
            $('img.PREVIEW_PIC.zoomable').draggable();
        }

        /**Resize video on edit**/
        if(currentRecord.type == 'video') {
            resizeVideo();
            $('#phraseanet-embed-editor-frame').css('text-align','center');
            /*resize of VIDEO */
            function resizeVideo(){
                if($('#phraseanet-embed-editor-frame iframe').length > 0) {

                    var $sel = $('#phraseanet-embed-editor-frame');
                    var $window =  $('#TH_Opreview').height();

                    // V is for "video" ; K is for "container" ; N is for "new"
                    var VW = $('#phraseanet-embed-editor-frame ').data( "original-width" );
                    var VH = $('#phraseanet-embed-editor-frame ').data( "original-height" );

                    var KW = $sel.width();
                    var KH = $sel.height();
                    KH = $window - 20 ;

                    var NW, NH;
                    if( (NH = (VH / VW) * (NW=KW) ) > KH )  {   // try to fit exact horizontally, adjust vertically
                        // too bad... new height overflows container height
                        NW = (VW / VH) * (NH=KH);      // so fit exact vertically, adjust horizontally
                    }
                    $("#phraseanet-embed-editor-frame iframe").css('width', NW).css('height', NH);

                }
            }
            $(window).on("load resize ",function(e){
                resizeVideo();
            });
            $(window).click(".ui-tabs-anchor", function (e) {
                resizeVideo();
            });
        }
        /**end Resize video on edit**/
    }

    /**
     * refresh preview if only one record is selected
     * @param params
     */
    function onSelectionChange(params) {
        let { selection, selectionPos } = params;
        if (selectionPos.length === 1) {
            renderPreview({
                recordIndex: selectionPos[0]
            });
        } else {
            // no preview to display
            renderPreview({
                recordIndex: null
            });
        }
    }

    return { initialize };
};
export default preview;
