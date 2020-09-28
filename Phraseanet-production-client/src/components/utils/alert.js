import $ from 'jquery';

function create_dialog() {
    if ($('#p4_alerts').length === 0) {
        $('body').append('<div id="p4_alerts"></div>');
    }
    return $('#p4_alerts');
}

function alert(title, message, callback) {
    var $dialog = create_dialog();

    var button = {};

    button.Ok = function () {
        if (typeof callback === 'function') {
            callback();
        } else {
            $dialog.dialog('close');
        }
    };
    if ($dialog.data('ui-dialog')) {
        $dialog.dialog('destroy');
    }

    $dialog.attr('title', title)
        .empty()
        .append(message)
        .dialog({
            autoOpen: false,
            closeOnEscape: true,
            resizable: false,
            draggable: false,
            modal: true,
            buttons: button,
            overlay: {
                backgroundColor: '#000',
                opacity: 0.7
            }
        }).dialog('open');

    if (typeof callback === 'function') {
        $dialog.bind('dialogclose', function (event, ui) {
            callback();
        });
    }

    return;
}

const Alerts = alert;

export default Alerts;
