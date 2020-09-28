import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
const humane = require('humane-js');

let $dialog = null;

const moveRecord = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');

    const openModal = (datas) => {
        $dialog = dialog.create(services, {
            size: 'Small phrasea-black-dialog',
            title: localeService.t('move'),
            closeButton: true,
        });
        //Add custom class to dialog wrapper
        $('.phrasea-black-dialog').closest('.ui-dialog').addClass('black-dialog-wrap move-dialog');

        return _getMovableRecords(datas)
            .then((data) => {
                if (data.success !== undefined) {
                    if (data.success === true) {
                        $dialog.setContent(data.template);
                        _bindFormEvents();
                    } else {
                        if (data.message !== undefined) {
                            $dialog.setContent(data.message);
                        }
                    }
                }
            }, (data) => {
                if (data.message !== undefined) {
                    $dialog.setContent(data.message);
                }

            });
    };

    const _bindFormEvents = () => {
        $dialog = dialog.get(1);

        var $form = $dialog.getDomElement();
        var buttons = {};

        buttons[localeService.t('valider')] = function () {
            var coll_son = $('input[name="chg_coll_son"]:checked', $form).length > 0 ? '1' : '0';
            var datas = {
                lst: $('input[name="lst"]', $form).val(),
                base_id: $('select[name="base_id"]', $form).val(),
                chg_coll_son: coll_son
            };

            var buttonPanel = $dialog.getDomElement()
                .closest('.ui-dialog')
                .find('.ui-dialog-buttonpane');


            $(":button:contains('" + localeService.t('valider') + "')", buttonPanel)
                .attr('disabled', true).addClass('ui-state-disabled');

            _postMovableRecords(datas).then(
                (data) => {
                    $dialog.close();
                    if (data.success) {
                        humane.info(data.message);
                    } else {
                        humane.error(data.message);
                    }
                    $(":button:contains('" + localeService.t('valider') + "')", buttonPanel)
                        .attr('disabled', false).removeClass('ui-state-disabled');
                },
                () => {

                }
            );

            return false;
        };

        $dialog.setOption('buttons', buttons);
    };

    const _getMovableRecords = (datas) => {
        return $.ajax({
            type: 'POST',
            url: `${url}prod/records/movecollection/`,
            data: datas
        });
    };

    const _postMovableRecords = (datas) => {
        return $.ajax({
            type: 'POST',
            url: `${url}prod/records/movecollection/apply/`,
            dataType: 'json',
            data: datas
        });
    };

    return { openModal };
};

export default moveRecord;
