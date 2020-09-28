import $ from 'jquery';
import dialog from './../../../phraseanet-common/components/dialog';

const recordBridge = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let pub_tabs = $('#pub_tabs');
    let container = $('#dialog_publicator');
    let managerUrl = container.data('url');
    let $panel;
    const initialize = () => {
        pub_tabs.tabs({
            beforeLoad: function (event, ui) {
                ui.tab.html_tab = ui.tab.find('span').html();
                ui.tab.find('span').html('<i>' + localeService.t('Loading') + '...</i>');
            },
            load: function (event, ui) {
                ui.tab.find('span').empty().append(ui.tab.html_tab);
                $panel = $(ui.panel);
                $('.container-bridge', $panel).removeClass('loading');
                $panel.addClass('PNB');
                $panel.wrapInner("<div class='PNB10 container-bridge' />");
                panel_load($panel);
            },
            beforeActivate: function (event, ui) {
                if ($(ui.tab).hasClass('account')) {
                    var container = $('.container-bridge', ui.panel);
                    container.empty();
                    $('.container', ui.panel).addClass('loading');
                }
            }
        }).addClass('ui-tabs-vertical ui-helper-clearfix');

        $('.ui-tabs-nav', pub_tabs).removeClass('ui-corner-all');


        $('.new_bridge_button', pub_tabs).bind('click', function () {
            var url = $(this).parent('form').find('input[name="url"]').val();
            popme(url);

            return false;
        });

        $('ul li a.account', pub_tabs).bind('click', function () {
            $('#dialog_publicator form[name="current_datas"] input[name="account_id"]').val($('input[name="account_id"]', this).val());
        });

        $('ul li.ui-tabs-selected a.account', pub_tabs).trigger('click');

        $('#publicator_selection .PNB10:first').selectable();

        $('#publicator_selection button.act_upload').bind('click', function () {

            var $this = $(this);
            var $form = $this.closest('form');

            $('input[name=lst]', $form).val(
                $.makeArray(
                    $('#publicator_selection .diapo.ui-selected').map(function (i, el) {
                        return $(el).attr('id').split('_').slice(2, 4).join('_');
                    })
                ).join(';')
            );

            var account_id = $('form[name="current_datas"] input[name="account_id"]').val();
            $('input[name="account_id"]', $form).val(account_id);

            var $panel = $('#pub_tabs .ui-tabs-panel:visible');

            $.ajax({
                type: 'GET',
                url: `${url}prod/bridge/upload/`,
                data: $form.serializeArray(),
                beforeSend: function () {
                    $panel.empty().addClass('loading');
                },
                success: function (datas) {
                    $panel.removeClass('loading').append(datas);
                    panel_load($panel);
                },
                error: function () {
                    $panel.removeClass('loading');
                },
                timeout: function () {
                    $panel.removeClass('loading');
                }
            });

            return false;
        });


        $('li', pub_tabs).removeClass('ui-corner-top').addClass('ui-corner-left');

        $('#api_connexion').click(function () {
            if (container.data('ui-dialog')) {
                container.dialog('close');
            }
        });
    };


    function popme(url) {
        var newwindow = window.open(url, 'logger', 'height=500,width=800');
        if (window.focus) {
            newwindow.focus();
        }

        return false;
    }

    function panel_load($panel) {
        $('.new_bridge_button', $panel).bind('click', function () {
            var url = $(this).parent('form').find('input[name="url"]').val();
            popme(url);

            return false;
        });

        $('.error_box, .notice_box', $panel).delay(10000).fadeOut();

        $('.back_link', $panel).bind('click', function () {
            if ($('#pub_tabs').data('ui-tabs')) {
                $('#pub_tabs').tabs('load', $('#pub_tabs').tabs('option', 'active'));
            }

            return false;
        });

        $('.bridge_action', $panel).bind('click', function () {
            var $this = $(this);

            $.ajax({
                type: 'GET',
                url: $(this).attr('href'),
                beforeSend: function () {
                    var container = $('.container-bridge', $panel);
                    container.empty();
                    if (!$this.hasClass('bridge_logout')) {
                        container.addClass('loading');
                    }
                },
                success: function (datas) {
                    $('.container-bridge', $panel).removeClass('loading').append(datas);
                    panel_load($panel);
                },
                error: function () {
                    $panel.removeClass('loading');
                },
                timeout: function () {
                    $panel.removeClass('loading');
                }
            });

            return false;
        });

        $('.delete-account', $panel).bind('click', function () {
            let account_id = $(this).val();
            let buttons = {};

            buttons[localeService.t('valider')] = function () {
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: `${url}prod/bridge/adapter/${account_id}/delete/`,
                    data: {},
                    success: function (datas) {
                        if (datas.success) {
                            confirmBox.close();
                            appEvents.emit('push.reload', managerUrl);
                            // pushModule.reloadBridge(managerUrl);
                        } else {
                            confirmBox.close();
                            var alertBox = dialog.create(services, {
                                size: 'Alert',
                                closeOnEscape: true,
                                closeButton: true
                            }, 2);

                            alertBox.setContent(datas.message);
                        }
                    }
                });
            };

            var confirmBox = dialog.create(services, {
                size: 'Alert',
                closeOnEscape: true,
                closeButton: true,
                cancelButton: true,
                buttons: buttons
            }, 2);

            confirmBox.setContent(localeService.t('You are about to delete this account. Would you like to continue ?'));
        });

        $('.form_submitter', $panel).bind('click', function () {
            var $form = $(this).closest('form');
            var method = $form.attr('method');

            method = $.inArray(method.toLowerCase(), ['post', 'get']) ? method : 'POST';

            $.ajax({
                type: method,
                url: $form.attr('action'),
                data: $form.serializeArray(),
                beforeSend: function () {
                    $panel.empty().addClass('loading');
                },
                success: function (datas) {
                    $panel.removeClass('loading').append(datas);
                    panel_load($panel);
                },
                error: function () {
                    $panel.removeClass('loading');
                },
                timeout: function () {
                    $panel.removeClass('loading');
                }
            });

            return false;
        });


        $('.bridge_all_selector', $panel).bind('click', function () {
            var checkboxes = $('.bridge_element_selector', $panel);
            var $this = $(this);

            checkboxes.each(function (i, checkbox) {
                if ($(checkbox).is(':checked') !== $this.is(':checked')) {
                    var event = $.Event('click');
                    event.selector_all = true;
                    $(checkbox).trigger(event);
                }
            });
        });

        $('.bridge_element_selector', $panel)
            .bind('click', function (event) {

                var $this = $(this);

                if (event.selector_all) {
                    $this.prop('checked', $('.bridge_all_selector', $panel).is(':checked'));
                }

                $('form[name="bridge_selection"] input[name="elements_list"]', $panel).val(
                    $.makeArray($('.bridge_element_selector:checked', $panel).map(function (i, el) {
                        return ($(el).val());
                    })).join(';')
                );

                if ($this.is(':checked')) {
                    $this.closest('.element').addClass('selected');
                } else {
                    $this.closest('.element').removeClass('selected');
                }

                if (!event.selector_all) {
                    var bool = !($('.bridge_element_selector:checked', $panel).length !== $('.bridge_element_selector', $panel).length);
                    $('.bridge_all_selector', $panel).prop('checked', bool);
                } else {
                    if (event.stopPropagation) {
                        event.stopPropagation();
                    }

                    return false;
                }
            });


        $('a.form_multiple_submitter', $panel).bind('click', function () {

            var $form = $(this).closest('form');
            var elements = $('form[name="bridge_selection"] input[name="elements_list"]', $panel).val();

            var n_elements = 0;
            if ($.trim(elements) !== '') {
                n_elements = elements.split(';').length;
            }

            if (n_elements === 0 && $form.hasClass('action_works_standalone') === false) {
                alert('No records selected');

                return false;
            }
            if (n_elements === 1 && $form.hasClass('action_works_single_element') === false) {
                alert('This action works only with a single records');

                return false;
            }
            if (n_elements > 1 && $form.hasClass('action_works_many_element') === false) {
                alert('This action works only with many records');

                return false;
            }

            $('input[name="elements_list"]', $form).val(elements);

            $.ajax({
                type: 'GET',
                url: $form.attr('action'),
                data: $form.serializeArray(),
                beforeSend: function () {
                    $panel.empty().addClass('loading');
                },
                success: function (datas) {
                    $panel.removeClass('loading').append(datas);
                    panel_load($panel);
                },
                error: function () {
                    $panel.removeClass('loading');
                },
                timeout: function () {
                    $panel.removeClass('loading');
                }
            });

            return false;

        });
    }


    return {
        initialize
    };
};
export default recordBridge;
