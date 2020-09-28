import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';

const propertyRecord = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');

    const openModal = (datas) => {
        return doProperty(datas);
    };

    const doProperty = (datas) => {
        var $dialog = dialog.create(services, {
            size: 'Medium',
            title: $('#property-title').val()
        });

        $.ajax({
            type: 'GET',
            data: datas,
            url: `${url}prod/records/property/`,
            success: function (data) {
                $dialog.setContent(data);
                _onPropertyReady($dialog);
            }
        });

        return true;
    };

    const _onPropertyReady = ($dialog) => {
        $('#tabs-records-property').tabs({
            beforeLoad: function (event, ui) {

                ui.ajaxSettings.data = {
                    lst: $('input[name=original_selection]', $(this)).val(),
                };

                // load template only once
                if (ui.tab.data('loaded')) {
                    event.preventDefault();
                    return;
                }

                ui.jqXHR.success(function () {
                    ui.tab.data('loaded', true);
                    ui.tab.find('span').html('');
                    typeTabContent($dialog, '#' + ui.tab.attr('aria-controls'));
                });

                ui.tab.find('span').html('<i>' + localeService.t('loading') + '</i>');
            },
            load: function (event, ui) {
                ui.tab.find('span').empty();
            }
        });
        propertyTabContent($dialog);


    };
    /**
     * Property Tab
     * @param $dialogBox
     */
    const propertyTabContent = ($dialog) => {

        const $propertyContainer = $('#property-status');

        $propertyContainer.on('click', 'button.cancel', function () {
            $dialog.close();
        });

        $propertyContainer.on('click', 'button.submiter', function () {
            var $this = $(this);
            var form = $(this).closest('form');
            var loader = form.find('form-action-loader');

            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serializeArray(),
                dataType: 'json',
                beforeSend: function () {
                    $this.attr('disabled', true);
                    loader.show();
                },
                success: function (data) {
                    $dialog.close();
                },
                complete: function () {
                    $this.attr('disabled', false);
                    loader.hide();
                }
            });
        });
    };
    /**
     * Type Tab
     * @param $dialog
     * @param typeContainerId
     */
    const typeTabContent = ($dialog, typeContainerId) => {

        const $typeContainer = $(typeContainerId);

        $typeContainer.on('click', 'button.cancel', function () {
            $dialog.close();
        });
        $typeContainer.on('click', 'button.submiter', function () {
            var $this = $(this);
            var form = $(this).closest('form');
            var loader = form.find('form-action-loader');

            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serializeArray(),
                dataType: 'json',
                beforeSend: function () {
                    $this.attr('disabled', true);
                    loader.show();
                },
                success: function (data) {
                    $dialog.close();
                },
                complete: function () {
                    $this.attr('disabled', false);
                    loader.hide();
                }
            });
        });
    };

    return {openModal};
};

export default propertyRecord;
