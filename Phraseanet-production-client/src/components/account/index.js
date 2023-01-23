/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';
import geonames from './../authentication/common/geonames';
const account = (services) => {
    const {configService, localeService, appEvents} = services;

    const initialize = (options) => {
        let {$container} = options;
        $container.on('click', '.alert .alert-block-close a', function (e) {
            e.preventDefault();
            $(this).closest('.alert').alert('close');
            return false;
        });

        // revoke third party application access
        $('a.app-btn').bind('click', function (e) {
            e.preventDefault();
            var $this = $(this);
            $.ajax({
                type: 'GET',
                url: $this.attr('href'),
                dataType: 'json',
                data: {revoke: $this.hasClass('authorize') ? 0 : 1},
                success: function (data) {
                    if (data.success) {
                        var li = $this.closest('li');

                        var hidden = $('.app-btn.hidden , .status.hidden', li);
                        var notHidden = $('.app-btn:not(.hidden), .status:not(.hidden)', li);

                        hidden.removeClass('hidden');
                        notHidden.addClass('hidden');
                    }
                }
            });
        });

        // generate new access token
        $('a#generate_access').bind('click', function (e) {
            e.preventDefault();
            var $this = $(this);
            $.ajax({
                type: 'POST',
                url: $this.attr('href'),
                dataType: 'json',
                data: {
                    usr_id: $this.closest('div').attr('id')
                },
                success: function (data) {
                    if (data.success) {
                        $('#my_access_token').empty().append(data.token);
                    }
                }
            });
        });

        //modify application callback url
        $('.modifier_callback').bind('click', function () {
            var modifierBtn = $(this);
            var saveBtn = $('a.save_callback');
            var input = $('.url_callback_input');
            var inputVal = input.html();

            modifierBtn.hide();
            saveBtn.show();
            // wrapp current calback in an input
            input
                .empty()
                .wrapInner(''
                    + '<input value="' + inputVal + '"'
                    + ' name="oauth_callback" size="50" type="text"/>'
                );

            $('.url_callback').off();

            // save new callback
            saveBtn.bind('click', function (e) {
                e.preventDefault();
                var callback = $('input[name=oauth_callback]').val();
                $.ajax({
                    type: 'POST',
                    url: saveBtn.attr('href'),
                    dataType: 'json',
                    data: {callback: callback},
                    success: function (data) {
                        if (data.success) {
                            input.empty().append(callback);
                        } else {
                            input.empty().append(inputVal);
                        }

                        modifierBtn.show();
                        saveBtn.hide();
                    }
                });
            });
        });

        //modify application webhook url
        $('.webhook-modify-btn').bind('click', function () {
            var modifierBtn = $(this);
            var saveBtn = $('a.save_webhook');
            var input = $('.url_webhook_input');
            var checkboxActive = $('.update-webhook-status');
            var inputVal = input.html();

            modifierBtn.hide();
            saveBtn.show();
            // wrapp current calback in an input
            input
                .empty()
                .wrapInner(''
                    + '<input value="' + inputVal + '"'
                    + ' name="oauth_webhook" size="50" type="text"/>'
                );

            $('.url_webhook').off();

            // save new callback
            saveBtn.bind('click', function (e) {
                e.preventDefault();
                var webhook = $('input[name=oauth_webhook]').val();
                $.ajax({
                    type: 'POST',
                    url: saveBtn.attr('href'),
                    dataType: 'json',
                    data: {webhook: webhook},
                    success: function (data) {
                        if (data.success) {
                            input.empty().append(webhook);
                            checkboxActive.prop('checked', true);
                        } else {
                            input.empty().append(inputVal);
                        }

                        modifierBtn.show();
                        saveBtn.hide();
                    }
                });
            });
        });

        // hide or show callback url input whether user choose a web or dektop application
     /*   $('#form_create input[name=type]').bind('click', function () {
            if ($(this).val() === 'desktop') {
                $('#form_create .callback-control-group').hide().find('input').val('');
            } else {
                $('#form_create .callback-control-group').show();
            }
        });*/

        // authorize password grant type or not
        $('.grant-type').bind('click', function () {
            var $this = $(this);
            $.ajax({
                type: 'POST',
                url: $this.attr('value'),
                dataType: 'json',
                data: {grant: $this.is(':checked') ? '1' : '0'},
                success: function (data) {
                }
            });
        });

        $container.on('click', '.update-listened-event', function () {
            let $this = $(this);
            $.ajax({
                type: 'POST',
                url: $this.attr('data-path'),
                dataType: 'json',
                data: {
                    action: $this.is(':checked') ? 'add' : 'remove'
                },
                success: function (data) {
                }
            });
        });

        $container.on('click', '.update-webhook-status', function () {
            let $this = $(this);
            $.ajax({
                type: 'POST',
                url: $this.attr('data-path'),
                dataType: 'json',
                data: {
                    action: $this.is(':checked') ? 'activate' : 'deactivate'
                },
                success: function (data) {
                }
            });
        });

        // delete an application
      /*  $('a.delete-app').bind('click', function (e) {
            e.preventDefault();
            var $this = $(this);
            var li = $this.closest('li');

            $.ajax({
                type: 'DELETE',
                url: $this.attr('href'),
                dataType: 'json',
                data: {},
                success: function (data) {
                    if (data.success) {
                        li.find('.modal').modal('hide');
                        li.remove();
                    }
                }
            });
        });*/
    };

    const editAccount = () => {
        $('legend').bind('click', function () {
            $('.form-info').hide(200);
            $($(this).data('target')).show();
        });

        geonames.init($('#form_geonameid'), {
            server: configService.get('geonameServerUrl'),
            limit: 40
        });
    };

    const editSession = () => {
        var modal = $('#modal-delete-confirm').modal({
            show: false
        });

        $('a.delete-session').bind('click', function (e) {
            e.preventDefault();
            modal
                .data('delete-url', $(this).prop('href'))
                .modal('toggle');

            return false;
        });

        $('a.confirm-delete').on('click', function (e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: modal.data('delete-url'),
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        $('#row-' + data.session_id).closest('tr').remove();
                    }
                    modal.modal('toggle');
                }
            });
        });
    };

    return {initialize, editAccount, editSession}
};
export default account;
