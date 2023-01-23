import $ from 'jquery';
import recordEditorService from './recordEditor/index';
import * as appCommons from './../../phraseanet-common';

const editRecord = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let $container = null;
    let recordEditor = recordEditorService(services);
    appEvents.listenAll({
        'record.doEdit': _doEdit
    });

    const initialize = () => {


        $container = $('body');
        $container.on('click', '.edit-record-action', function (event) {
            event.preventDefault();
            let $el = $(event.currentTarget);
            let type = '';
            let kind = $el.data('kind');
            let idContent = $el.data('id');

            switch (kind) {
                case 'basket':
                    type = 'SSTT';
                    break;
                case 'record':
                    type = 'IMGT';
                    break;
                default:
            }

            _doEdit({type: type, value: idContent});
        });


    };

    const openModal = (datas) => {
        $('#EDITWINDOW').empty().addClass('loading');
        //commonModule.showOverlay(2);

        $('#EDITWINDOW').show();

        $.ajax({
            url: `${url}prod/records/edit/`,
            type: 'POST',
            dataType: 'html',
            data: datas,
            success: (data) => {
                $('#EDITWINDOW').removeClass('loading').empty().html(data);

                if (window.recordEditorConfig.hasMultipleDatabases === true) {
                    $('#EDITWINDOW').removeClass('loading').hide();

                    return;
                }

                // let recordEditor = recordEditorService(services);
                recordEditor.initialize({
                    $container: $('#EDITWINDOW'),
                    recordConfig: window.recordEditorConfig
                });

                $('#tooltip').hide();
                return;
            },
            error: function (XHR, textStatus, errorThrown) {
                if (XHR.status === 0) {
                    return false;
                }
            }
        });

        return true;
    };

    // open Modal
    function _doEdit(options) {
        let {type, value} = options;
        var datas = {
            lst: '',
            ssel: '',
            act: ''
        };

        switch (type) {
            case 'IMGT':
                datas.lst = value;
                break;

            case 'SSTT':
                datas.ssel = value;
                break;

            case 'STORY':
                datas.story = value;
                break;
            default:
        }

        return openModal(datas);
    }

    const onGlobalKeydown = (event, specialKeyState) => {
        if (specialKeyState === undefined) {
            let specialKeyState = {
                isCancelKey: false,
                isShortcutKey: false
            };
        }
        switch (event.keyCode) {
            case 9: // tab ou shift-tab
                fieldNavigate(
                    event,
                    appCommons.utilsModule.is_shift_key(event) ? -1 : 1
                );
                specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                break;
            case 27:
                cancelChanges({ event });
                specialKeyState.isShortcutKey = true;
                break;

            case 33: // pg up
                if (
                    !options.textareaIsDirty ||
                    validateFieldChanges(event, 'ask_ok')
                ) {
                    skipImage(event, 1);
                }
                specialKeyState.isCancelKey = true;
                break;
            case 34: // pg dn
                if (
                    !options.textareaIsDirty ||
                    validateFieldChanges(event, 'ask_ok')
                ) {
                    skipImage(event, -1);
                }
                specialKeyState.isCancelKey = true;
                break;
            default:
        }
        return specialKeyState;
    };

    function fieldNavigate(evt, dir) {
        let current_field = $('#divS .edit_field.active');
        if (current_field.length === 0) {
            current_field = $('#divS .edit_field:first');
            current_field.trigger('click');
        } else {
            if (dir >= 0) {
                current_field.next().trigger('click');
            } else {
                current_field.prev().trigger('click');
            }
        }
    }

    return { initialize, openModal, onGlobalKeydown };
};

export default editRecord;
