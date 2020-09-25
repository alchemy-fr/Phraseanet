import $ from 'jquery';
import * as appCommons from './../../../phraseanet-common';
const keyboard = (services) => {
    const { configService, localeService, appEvents } = services;

    const initialize = () => {

    };

    const openModal = () => {
        $('#keyboard-stop').bind('click', function () {
            var display = $(this).get(0).checked ? '0' : '1';

            appCommons.userModule.setPref('keyboard_infos', display);

        });

        var buttons = {};

        buttons[localeService.t('fermer')] = function () {
            $('#keyboard-dialog').dialog('close');
        };

        $('#keyboard-dialog').dialog({
            closeOnEscape: false,
            resizable: false,
            draggable: false,
            modal: true,
            width: 600,
            height: 400,
            overlay: {
                backgroundColor: '#000',
                opacity: 0.7
            },
            open: function (event, ui) {
                $(this).dialog('widget').css('z-index', '1999');
            },
            close: function () {
                $(this).dialog('widget').css('z-index', 'auto');
                if ($('#keyboard-stop').get(0).checked) {
                    var dialog = $('#keyboard-dialog');
                    if (dialog.data('ui-dialog')) {
                        dialog.dialog('destroy');
                    }
                    dialog.remove();
                }
            }
        }).dialog('option', 'buttons', buttons).dialog('open');

        $('#keyboard-dialog').scrollTop(0);
    };

    return { initialize, openModal };
};

export default keyboard;
