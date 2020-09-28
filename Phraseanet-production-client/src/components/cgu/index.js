import $ from 'jquery';
import * as appCommons from './../../phraseanet-common';
const humane = require('humane-js');
const cgu = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');

    const initialize = (options = {}) => {
        const { $container } = options;
        var $this = $('.cgu-dialog:first');
        $this.dialog({
            autoOpen: true,
            closeOnEscape: false,
            draggable: false,
            modal: true,
            resizable: false,
            width: 800,
            height: 500,
            open: function () {
                $this.parents('.ui-dialog:first').find('.ui-dialog-titlebar-close').remove();
                $('.cgus-accept', $(this)).bind('click', function () {
                    acceptCgus($('.cgus-accept', $this).attr('id'), $('.cgus-accept', $this).attr('date'));
                    $this.dialog('close').remove();
                    initialize(services, options);
                });
                $('.cgus-cancel', $(this)).bind('click', function () {
                    if (confirm(localeService.t('warningDenyCgus'))) {
                        cancelCgus($('.cgus-cancel', $this).attr('id').split('_').pop());
                    }
                });
            }
        });
    };

    function acceptCgus(name, value) {
        appCommons.userModule.setPref(name, value);
    }

    function cancelCgus(id) {

        $.ajax({
            type: 'POST',
            url: `${url}prod/TOU/deny/${id}/`,
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    alert(localeService.t('cgusRelog'));
                    self.location.replace(self.location.href);
                } else {
                    humane.error(data.message);
                }
            }
        });

    }

    return {
        initialize
    };
};

export default cgu;
