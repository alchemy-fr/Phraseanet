import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
require('./../../phraseanet-common/components/tooltip');
import merge from 'lodash.merge';
const humane = require('humane-js');

const basketBrowse = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');

    const initialize = () => {
        $('body').on('click', '.basket-browse-action', (event) => {
            event.preventDefault();
            const $el = $(event.currentTarget);
            let dialogOptions = {};

            if ($el.attr('title') !== undefined) {
                dialogOptions.title = $el.attr('title');
                dialogOptions.width = 920;
            }

            openModal(dialogOptions);
        });

    };

    const openModal = (options = {}) => {

        let dialogOptions = merge({
            size: 'Medium',
            loading: false
        }, options);
        const $dialog = dialog.create(services, dialogOptions);

        return $.get(`${url}prod/WorkZone/Browse/`, function (data) {
            $dialog.setContent(data);
            _onDialogReady();
            return;
        });
    };

    const _onDialogReady = () => {
        const $container = $('#BasketBrowser');
        let results = null;

        function loadResults(datas, url) {
            let $results = $('.results', $container);
            results = $.ajax({
                type: 'GET',
                url: url,
                dataType: 'html',
                data: datas,
                beforeSend: function () {
                    if (results && results.abort && typeof results.abort === 'function') {
                        results.abort();
                    }
                    $results.addClass('loading').empty();
                },
                error: function () {
                    $results.removeClass('loading');
                },
                timeout: function () {
                    $results.removeClass('loading');
                },
                success: function (data) {
                    $results.removeClass('loading').append(data);
                    activateLinks($results);
                    active_archiver($results);

                    return;
                }

            });
        }


        function loadBasket(url) {
            results = $.ajax({
                type: 'GET',
                url,
                dataType: 'html',
                beforeSend: function () {
                    if (results && results.abort && typeof results.abort === 'function') {
                        results.abort();
                    }
                    $('.Browser', $container).hide();
                    $('.Basket', $container).addClass('loading').empty().show();
                },
                error: function () {
                    $('.Browser', $container).show();
                    $('.Basket', $container).removeClass('loading').hide();
                },
                timeout: function () {
                    $('.Browser', $container).show();
                    $('.Basket', $container).removeClass('loading').hide();
                },
                success: function (data) {
                    $('.Basket', $container).removeClass('loading').append(data);

                    $('.Basket a.back', $container).bind('click', function () {
                        $('.Basket', $container).hide();
                        $('.Browser', $container).show();

                        return false;
                    });
                    active_archiver($('.Basket', $container));

                    return;
                }

            });
        }

        function activateLinks($scope) {
            let confirmBox = {};
            let buttons = {};

            $('a.result', $scope).bind('click', function (event) {
                event.preventDefault();
                var $this = $(this);

                loadResults({}, $this.attr('href'));

                return false;
            });

            $('.result_page').bind('click', function (event) {
                event.preventDefault();
                var $this = $(this);

                loadResults({}, $this.attr('href'));

                return false;
            });

            $('a.basket_link', $scope).bind('click', function (event) {
                event.preventDefault();
                var $this = $(this);

                loadBasket($this.attr('href'));

                return false;
            });

            $('a.delete-basket', $scope).bind('click', function (event) {
                event.preventDefault();
                var $this = $(this);
                var buttons = {};

                buttons[localeService.t('valider')] = function () {
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: $this.attr('href'),
                        data: {},
                        success: function (datas) {
                            if (datas.success) {
                                confirmBox.close();
                                $('form[name="BasketBrowser"]', $container).trigger('submit');
                                appEvents.emit('workzone.refresh');
                            } else {
                                confirmBox.close();
                                var alertBox = dialog.create(services, {
                                    size: 'Alert',
                                    closeOnEscape: true,
                                    closeButton: true
                                }, 2);

                                alertBox.setContent(datas.message);
                            }
                        },
                        error: function () {
                            confirmBox.close();
                            var alertBox = dialog.create(services, {
                                size: 'Alert',
                                closeOnEscape: true,
                                closeButton: true
                            }, 2);

                            alertBox.setContent("{{'Something wrong happened, please retry or contact an admin.'|trans|e('js') }}");
                        }
                    });
                };

                confirmBox = dialog.create(services, {
                    size: 'Alert',
                    closeOnEscape: true,
                    cancelButton: true,
                    buttons: buttons
                }, 2);

                confirmBox.setContent("{{'You are about to delete this basket. Would you like to continue ?'|trans|e('js') }}");

                return false;
            });
        }

        function active_archiver($scope) {
            $('a.UserTips', $scope).bind('click', function () {

                return false;
            }).tooltip();

            $('.infoTips, .previewTips', $scope).tooltip();

            $('a.archive_toggler', $scope).bind('click', function (event) {
                event.preventDefault();
                const $this = $(this);
                const parent = $this.parent();

                $.ajax({
                    type: 'POST',
                    url: $this.attr('href'),
                    dataType: 'json',
                    beforeSend: function () {
                        $('.loader', parent).show();
                        $('.archive_toggler:visible', parent).addClass('last_act').hide();
                    },
                    error: function () {
                        $('.loader', parent).hide();
                        $('.last_act', parent).removeClass('last_act').show();
                    },
                    timeout: function () {
                        $('.loader', parent).hide();
                        $('.last_act', parent).removeClass('last_act').show();
                    },
                    success: function (data) {
                        $('.loader', parent).hide();
                        $('.last_act', parent).removeClass('last_act');
                        if (!data.success) {
                            humane.error(data.message);

                            return;
                        }
                        if (data.archive === true) {
                            $('.unarchiver', parent).show();
                            $('.archiver', parent).hide();
                            $($this).closest('.result').removeClass('unarchived');
                        } else {
                            $('.unarchiver', parent).hide();
                            $('.archiver', parent).show();
                            $($this).closest('.result').addClass('unarchived');
                        }

                        appEvents.emit('workzone.refresh');

                        return;
                    }

                });

                return false;
            });
        }

        $('form[name="BasketBrowser"]', $container).bind('submit', function () {

            let $this = $(this);

            loadResults($this.serializeArray(), $this.attr('action'));

            return false;
        }).trigger('submit').find('label').bind('click', function () {
            const input = $(this).prev('input');
            let inputs = $('input[name="' + $(this).prev('input').attr('name') + '"]', $container);
            inputs.prop('checked', false).next('label').removeClass('selected');

            input.prop('checked', true).next('label').addClass('selected');
            $('form[name="BasketBrowser"]', $container).trigger('submit');
        });
    };

    return {initialize};
};

export default basketBrowse;
