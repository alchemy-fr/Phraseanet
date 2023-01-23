import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
const humane = require('humane-js');

const exportRecord = services => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let $container = null;
    const initialize = () => {
        $container = $('body');
        $container.on('click', '.record-export-action', function (event) {
            event.preventDefault();
            let $el = $(event.currentTarget);
            let key = '';
            let kind = $el.data('kind');
            let idContent = $el.data('id');

            switch (kind) {
                case 'basket':
                    key = 'ssel';
                    break;
                case 'record':
                    key = 'lst';
                    break;
                default:
            }

            doExport(`${key}=${idContent}`);
        });
    };

    const openModal = datas => doExport(datas);

    function doExport(datas) {
        var $dialog = dialog.create(services, {
            size: 'Medium',
            title: localeService.t('export')
        });

        $.ajax({
            method: 'POST',
            url: `${url}prod/export/multi-export/`,
            data: datas,
            success: function (data) {
                $dialog.setContent(data);
                if (window.exportConfig.isGuest) {
                    dialog.get(1).close();
                    let guestModal = dialog.create(
                        {
                            size: '500x100',
                            closeOnEscape: true,
                            closeButton: false,
                            title: window.exportConfig.msg.modalTile
                        },
                        2
                    );
                    guestModal.setContent(window.exportConfig.msg.modalContent);
                } else {
                    _onExportReady($dialog, window.exportConfig);
                }
            }
        });

        return true;
    }

    const _onExportReady = ($dialog, dataConfig) => {
        $('.tabs', $dialog.getDomElement()).tabs();

        $('.close_button', $dialog.getDomElement()).bind('click', function () {
            $dialog.close();
        });

        var tabs = $('.tabs', $dialog.getDomElement());

        if (dataConfig.haveFtp === true) {
            $('#ftp_form_selector')
                .bind('change', function () {
                    $('#ftp .ftp_form').hide();
                    $('#ftp .ftp_form_' + $(this).val()).show();
                    $('.ftp_folder_check', dialog.get(1).getDomElement())
                        .unbind('change')
                        .bind('change', function () {
                            if ($(this).prop('checked')) {
                                $(this).next().prop('disabled', false);
                            } else {
                                $(this).next().prop('disabled', true);
                            }
                        });
                })
                .trigger('change');
        }

        $('a.TOUview').bind('click', function (event) {
            event.preventDefault();
            let $el = $(event.currentTarget);
            var options = {
                size: 'Medium',
                closeButton: true,
                title: dataConfig.msg.termOfUseTitle
            };

            let termOfuseDialog = dialog.create(services, options, 2);

            $.get($el.attr('href'), function (content) {
                termOfuseDialog.setContent(content);
            });
        });

        $('.close_button').bind('click', function () {
            $dialog.close();
        });

        $('#download .download_button').bind('click', function () {
            if (!check_subdefs($('#download'), dataConfig)) {
                return false;
            }

            if (!check_TOU($('#download'), dataConfig)) {
                return false;
            }

            var total = 0;
            var count = 0;

            $('input[name="obj[]"]', $('#download')).each(function () {
                var total_el = $(
                    '#download input[name=download_' + $(this).val() + ']'
                );
                var count_el = $(
                    '#download input[name=count_' + $(this).val() + ']'
                );
                if ($(this).prop('checked')) {
                    total += parseInt($(total_el).val(), 10);
                    count += parseInt($(count_el).val(), 10);
                }
            });

            if (count > 1 && total / 1024 / 1024 > dataConfig.maxDownload) {
                if (
                    confirm(
                        `${dataConfig.msg.fileTooLarge} \n ${dataConfig.msg
                            .fileTooLargeAlt}`
                    )
                ) {
                    $(
                        'input[name="obj[]"]:checked',
                        $('#download')
                    ).each(function (i, n) {
                        $(
                            'input[name="obj[]"][value="' + $(n).val() + '"]',
                            $('#sendmail')
                        ).prop('checked', true);
                    });

                    $(document).find('input[name="taglistdestmail"]').tagsinput('add', dataConfig.user.email);

                    var tabs = $('.tabs', $dialog.getDomElement());
                    tabs.tabs('option', 'active', 1);
                }

                return false;
            }
            $('#download form').submit();
            $dialog.close();
        });

        $('#order .order_button').bind('click', function () {
            let title = '';
            if (!check_TOU($('#order'), dataConfig)) {
                return false;
            }

            $('#order .order_button_loader').css('visibility', 'visible');

            var options = $('#order form').serialize();

            var $this = $(this);
            $this.prop('disabled', true).addClass('disabled');
            $.post(
                `${url}prod/order/`,
                options,
                function (data) {
                    $this.prop('disabled', false).removeClass('disabled');

                    $('#order .order_button_loader').css(
                        'visibility',
                        'hidden'
                    );

                    if (!data.error) {
                        title = dataConfig.msg.success;
                    } else {
                        title = dataConfig.msg.warning;
                    }

                    var options = {
                        size: 'Alert',
                        closeButton: true,
                        title: title
                    };

                    dialog.create(services, options, 2).setContent(data.msg);

                    if (!data.error) {
                        humane.info(data.msg);
                        $dialog.close();
                    } else {
                        humane.error(data.msg);
                    }

                    return;
                },
                'json'
            );
        });

        $('#ftp .ftp_button').bind('click', function () {
            if (!check_subdefs($('#ftp'), dataConfig)) {
                return false;
            }

            if (!check_TOU($('#ftp'), dataConfig)) {
                return false;
            }

            $('#ftp .ftp_button_loader').show();

            $('#ftp .ftp_form:hidden').remove();

            var $this = $(this);

            var options_addr = $('#ftp_form_stock form:visible').serialize();
            var options_join = $('#ftp_joined').serialize();

            $this.prop('disabled', true);
            $.post(
                `${url}prod/export/ftp/`,
                options_addr + '&' + options_join,
                function (data) {
                    $this.prop('disabled', false);
                    $('#ftp .ftp_button_loader').hide();

                    if (data.success) {
                        humane.info(data.message);
                        $dialog.close();
                    } else {
                        var alert = dialog.create(
                            services,
                            {
                                size: 'Alert',
                                closeOnEscape: true,
                                closeButton: true,
                                title: dataConfig.msg.warning
                            },
                            2
                        );

                        alert.setContent(data.message);
                    }
                    return;
                },
                'json'
            );
        });

        $('#ftp .tryftp_button').bind('click', function () {
            $('#ftp .tryftp_button_loader').css('visibility', 'visible');
            var $this = $(this);
            $this.prop('disabled', true);
            var options_addr = $('#ftp_form_stock form:visible').serialize();

            $.post(
                `${url}prod/export/ftp/test/`,
                // no need to include 'ftp_joined' checkboxes to test ftp
                options_addr,
                function (data) {
                    $('#ftp .tryftp_button_loader').css('visibility', 'hidden');

                    var options = {
                        size: 'Alert',
                        closeButton: true,
                        title: data.success
                            ? dataConfig.msg.success
                            : dataConfig.msg.warning
                    };

                    dialog
                        .create(services, options, 3)
                        .setContent(data.message);

                    $this.prop('disabled', false);

                    return;
                }
            );
        });

        $('#sendmail .sendmail_button').bind('click', function () {
            if(!validEmail($('input[name="taglistdestmail"]', $('#sendmail')).val(), dataConfig)) {
                return false;
            }

            if (!check_subdefs($('#sendmail'), dataConfig)) {
                return false;
            }

            if (!check_TOU($('#sendmail'), dataConfig)) {
                return false;
            }

            if ($('iframe[name=""]').length === 0) {
                $('body').append(
                    '<iframe style="display:none;" name="sendmail_target"></iframe>'
                );
            }

            $('#sendmail form').submit();
            humane.infoLarge($('#export-send-mail-notif').val());
            $dialog.close();
        });

        $('.datepicker', $dialog.getDomElement()).datepicker({
            changeYear: true,
            changeMonth: true,
            dateFormat: 'yy-mm-dd'
        });

        $(
            'a.undisposable_link',
            $dialog.getDomElement()
        ).bind('click', function () {
            $(this).parent().parent().find('.undisposable').slideToggle();
            return false;
        });

        $(
            'input[name="obj[]"]',
            $('#download, #sendmail, #ftp')
        ).bind('change', function () {
            var $form = $(this).closest('form');

            if ($('input.caption[name="obj[]"]:checked', $form).length > 0) {
                $('div.businessfields', $form).show();
            } else {
                $('div.businessfields', $form).hide();
            }
        });
    };

    function validateEmail(email) {
        var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(email);
    }

    function validEmail(emailList, dataConfig) {
        //split emailList by ; , or whitespace and filter empty element
        let emails = emailList.split(/[ ,;]+/).filter(Boolean);
        let alert;
        for(let i=0; i < emails.length; i++) {
            if (!validateEmail(emails[i])) {

                alert = dialog.create(
                    services,
                    {
                        size: 'Alert',
                        closeOnEscape: true,
                        closeButton: true,
                        title: dataConfig.msg.warning
                    },
                    2
                );

                alert.setContent(dataConfig.msg.invalidEmail);
                return false;
            }
        }
        return true;
    }


    function check_TOU(container, dataConfig) {
        let checkbox = $('input[name="TOU_accept"]', $(container));
        let go = checkbox.length === 0 || checkbox.prop('checked');
        let alert;
        if (!go) {
            alert = dialog.create(
                services,
                {
                    size: 'Small',
                    closeOnEscape: true,
                    closeButton: true,
                    title: dataConfig.msg.warning
                },
                2
            );

            alert.setContent(dataConfig.msg.termOfUseAgree);

            return false;
        }
        return true;
    }

    function check_subdefs(container, dataConfig) {
        let go = false;
        let required = false;
        let alert;

        $('input[name="obj[]"]', $(container)).each(function () {
            if ($(this).prop('checked')) {
                go = true;
            }
        });

        $('input.required, textarea.required', container).each(function (i, n) {
            if ($.trim($(n).val()) === '') {
                required = true;
                $(n).addClass('error');
            } else {
                $(n).removeClass('error');
            }
        });

        if (required) {
            alert = dialog.create(
                services,
                {
                    size: 'Alert',
                    closeOnEscape: true,
                    closeButton: true,
                    title: dataConfig.msg.warning
                },
                2
            );

            alert.setContent(dataConfig.msg.requiredFields);

            return false;
        }
        if (!go) {
            alert = dialog.create(
                services,
                {
                    size: 'Alert',
                    closeOnEscape: true,
                    closeButton: true,
                    title: dataConfig.msg.warning
                },
                2
            );

            alert.setContent(dataConfig.msg.missingSubdef);

            return false;
        }

        return true;
    }

    return { initialize, openModal };
};

export default exportRecord;
