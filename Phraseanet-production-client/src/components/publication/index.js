import $ from 'jquery';
let lazyload = require('jquery-lazyload');
import dialog from './../../phraseanet-common/components/dialog';

const publication = (services) => {
    let ajaxState = {
        query: null,
        isRunning: false
    };
    let $answers;
    let curPage;
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');

    const initialize = () => {

        $answers = $('#answers');

        // refresh current view
        $answers.on('click', '.feed_reload', function (event) {
            event.preventDefault();
            fetchPublications(curPage);
        });

        // navigate to a specific feed
        $answers.on('click', '.ajax_answers', function (event) {
            event.preventDefault();
            var $this = $(this);
            var append = $this.hasClass('append');
            var noScroll = $this.hasClass('no_scroll');

            _fetchRemote($(event.currentTarget).attr('href'), {})
                .then(function (data) {
                    if (!append) {
                        $answers.empty();
                        if (!noScroll) {
                            $answers.scrollTop(0);
                        }
                        $answers.append(data);

                        $answers.find('img.lazyload').lazyload({
                            container: $answers
                        });
                    } else {
                        $('.see_more.loading', $answers).remove();
                        $answers.append(data);

                        $answers.find('img.lazyload').lazyload({
                            container: $answers
                        });

                        if (!noScroll) {
                            $answers.animate({
                                scrollTop: ($answers.scrollTop() + $answers.innerHeight() - 80)
                            });
                        }
                    }
                    appEvents.emit('search.doAfterSearch');
                });

        });

        // subscribe_rss
        $answers.on('click', '.subscribe_rss', function (event) {
            event.preventDefault();
            let $this = $(this);
            let renew = false;

            /*if (typeof (renew) === 'undefined') {
             renew = 'false';
             } else {
             renew = renew ? 'true' : 'false';
             }*/

            var buttons = {};
            buttons[localeService.t('renewRss')] = function () {
                $this.trigger({
                    type: 'click',
                    renew: true
                });
            };
            buttons[localeService.t('fermer')] = function () {
                $('#DIALOG').empty().dialog('destroy');
            };

            event.stopPropagation();

            $.ajax({
                type: 'GET',
                url: $this.attr('href') + (event.renew === true ? '?renew=true' : ''),
                dataType: 'json',
                success: function (data) {
                    if (data.texte !== false && data.titre !== false) {
                        if ($('#DIALOG').data('ui-dialog')) {
                            $('#DIALOG').dialog('destroy');
                        }
                        $('#DIALOG').attr('title', data.titre)
                            .empty()
                            .append(data.texte)
                            .dialog({
                                autoOpen: false,
                                closeOnEscape: true,
                                resizable: false,
                                draggable: false,
                                modal: true,
                                buttons: buttons,
                                width: 650,
                                height: 250,
                                overlay: {
                                    backgroundColor: '#000',
                                    opacity: 0.7
                                },
                                open: function() {
                                    $('#copy-feed').on('click', function (event) {
                                        event.preventDefault();
                                        var copyText = document.getElementById('input-select-copy');
                                        /* Select the text field */
                                        copyText.select();
                                        copyText.setSelectionRange(0, 99999); /*For mobile devices*/

                                        document.execCommand('copy');
                                    });
                                },
                            }).dialog('open');

                    }
                }
            });
        });

        // edit a feed
        $answers.on('click', '.feed .entry a.feed_edit', function () {
            var $this = $(this);
            $.ajax({
                type: 'GET',
                url: $this.attr('href'),
                dataType: 'html',
                success: function (data) {
                    return openModal(data);
                }
            });
            return false;
        });

        // remove a feed
        $answers.on('click', '.feed .entry a.feed_delete', function () {
            if (!confirm('etes vous sur de vouloir supprimer cette entree ?')) {
                return false;
            }
            var $this = $(this);
            $.ajax({
                type: 'POST',
                url: $this.attr('href'),
                dataType: 'json',
                success: function (data) {
                    if (data.error === false) {
                        var $entry = $this.closest('.entry');
                        $entry.animate({
                            height: 0,
                            opacity: 0
                        }, function () {
                            $entry.remove();
                        });
                    } else {
                        alert(data.message);
                    }
                }
            });
            return false;
        });


        $answers.on('mouseover', '.feed .entry', function () {
            $(this).addClass('hover');
        });

        $answers.on('mouseout', '.feed .entry', function () {
            $(this).removeClass('hover');
        });

        $answers.on('click', '.see_more a', function (event) {
            const $see_more = $(this).closest('.see_more');
            $see_more.addClass('loading');
        });
    };


    const _fetchRemote = function (url, data) {
        let page = 0;
        if (data.page === undefined) {
            page = data.page;
        }

        ajaxState.query = $.ajax({
            type: 'GET',
            url: url,
            dataType: 'html',
            data: data,
            beforeSend: function () {
                if (ajaxState.isRunning && ajaxState.query.abort) {
                    ajaxState.query.abort();
                }
                if (page === 0) {
                    appEvents.emit('search.doClearSearch');
                }
                ajaxState.isRunning = true;
                $answers.addClass('loading');
            },
            error: function () {
                ajaxState.isRunning = false;
                $answers.removeClass('loading');
            },
            timeout: function () {
                ajaxState.isRunning = false;
                $answers.removeClass('loading');
            },
            success: function (data) {
                ajaxState.isRunning = false;
            }
        });
        return ajaxState.query;
    };

    function checkFeedVFieldError() {
        if ($('.feed_warning').hasClass('alert-error')) {
            $('.ui-dialog-buttons').find('button').eq(1).prop('disabled', true);
        } else {
            $('.ui-dialog-buttons').find('button').eq(1).prop('disabled', false);
        }
    }

    function feedFieldValidator (field, feedWarning , length) {
        field.on('change keyup',function () {
            if ( $(this).val().length > length){
                feedWarning.addClass('alert alert-error');
            }else {
                feedWarning.removeClass('alert alert-error');
            }
            checkFeedVFieldError();
        });
    }
    var openModal = function (data) {
        let buttons = {};
        let modal = dialog.create(services, {
            size: 'Full',
            closeOnEscape: true,
            closeButton: true,
            title: localeService.t('Publication')
        });
        //Add custom class to dialog wrapper
        $('.dialog-Full').closest('.ui-dialog').addClass('black-dialog-wrap publish-dialog');

        modal.setContent(data);

        buttons[localeService.t('valider')] = onSubmitPublication;

        modal.setOption('buttons', buttons);
        let $feeds_item = $('.feeds .feed', modal.getDomElement());
        let $form = $('form.main_form', modal.getDomElement());
        let $feed_title_field = $('#feed_add_title', modal.getDomElement());
        let $feed_title_warning = $('.feed_title_warning', modal.getDomElement());
        let $feed_subtitle_field = $('#feed_add_subtitle', modal.getDomElement());
        let $feed_subtitle_warning = $('.feed_subtitle_warning', modal.getDomElement());
        let $feed_add_notify = $('#feed_add_notify', modal.getDomElement());
        feedFieldValidator($feed_title_field,$feed_title_warning, 128);
        feedFieldValidator($feed_subtitle_field,$feed_subtitle_warning, 1024);

        $feeds_item.bind('click', function () {
            $feeds_item.removeClass('selected');
            $(this).addClass('selected');
            $('input[name="feed_id"]', $form).val($('input', this).val());
            if ($('#modal_feed #feed_add_notify').is(':checked')) {
                getUserCount();
            } else {
                $('#publication-notify-message').empty();
            }
        }).hover(function () {
            $(this).addClass('hover');
        }, function () {
            $(this).removeClass('hover');
        });

        $form.bind('submit', function () {
            return false;
        });

        let formMode = 'create';
        // is edit mode?
        if ($('input[name="item_id"]').length > 0) {
            formMode = 'edit';
        }

        $('#modal_feed .record_list').sortable({
            placeholder: 'ui-state-highlight',
            stop: function (event, ui) {


                var lst = [];
                $('#modal_feed  .record_list .sortable form').each(function (i, el) {
                    if (formMode === 'create') {
                        lst.push($('input[name="sbas_id"]', el).val() + '_' + $('input[name="record_id"]', el).val());
                    } else {
                        lst.push($('input[name="item_id"]', el).val());

                    }
                });
                $('#modal_feed form.main_form input[name="lst"]').val(lst.join(';'));
            }
        });

        $('#modal_feed #feed_add_notify').on('click', function() {
            let $this = $(this);

            if ($this.is(':checked')) {
                getUserCount();
            } else {
                $('#publication-notify-message').empty();
            }
        });

        return;
    };

    var getUserCount = function () {
        $.ajax({
            type: 'POST',
            url: '/prod/feeds/notify/count/',
            dataType: 'json',
            data: {
                feed_id: $('#modal_feed input[name="feed_id"]').val(),
            },
            beforeSend: function () {
                $('#publication-notify-message').empty().html('<img src="/assets/common/images/icons/main-loader.gif" alt="loading"/>');
            },
            success: function (data) {
                if (data.success) {
                    $('#publication-notify-message').empty().append(data.message);
                } else {
                    $('#publication-notify-message').empty().append(data.message);
                    $('#modal_feed #feed_add_notify').prop('checked', false);
                }
            }
        });
    };

    const onSubmitPublication = () => {
        var $dialog = dialog.get(1);
        var error = false;
        $('.publish-dialog button').prop('disabled', true);

        var $form = $('form.main_form', $dialog.getDomElement());

        $('.required_text', $form).each(function (i, el) {
            if ($.trim($(el).val()) === '') {
                $(el).addClass('error');
                error = true;
            }
        });

        if (error) {
            alert(localeService.t('feed_require_fields'));
            $('.publish-dialog button').prop('disabled', false);
        }

        if ($('input[name="feed_id"]', $form).val() === '') {
            alert(localeService.t('feed_require_feed'));
            error = true;
        }

        if (error) {
            $('.publish-dialog button').prop('disabled', false);
            return false;
        }

        $.ajax({
            type: 'POST',
            url: $form.attr('action'),
            data: $form.serializeArray(),
            dataType: 'json',
            beforeSend: function () {
                $('.publish-dialog button').prop('disabled', true);
            },
            error: function () {
                $('.publish-dialog button').prop('disabled', false);
            },
            timeout: function () {
                $('.publish-dialog button').prop('disabled', false);
            },
            success: function (data) {
                $('.state-navigation').trigger('click');
                if (data.error === true) {
                    alert(data.message);
                    $('.publish-dialog button').prop('disabled', false);
                    return;
                }

                if ($('form.main_form', $dialog.getDomElement()).hasClass('entry_update')) {
                    var id = $('form input[name="entry_id"]', $dialog.getDomElement()).val();
                    var container = $('#entry_' + id);

                    container.replaceWith(data.datas);

                    container.hide().fadeIn();

                    // @TODO: something was happening here
                    $answers.find('img.lazyload').lazyload({
                        container: $answers
                    });
                }

                $('.publish-dialog button').prop('disabled', false);
                $dialog.close(1);
            }
        });
    }

    var fetchPublications = function (page) {
        if (page === undefined && $answers !== undefined) {
            // @TODO $answers can be undefined
            $answers.empty();
        }
        if ($answers !== undefined) {
            curPage = page;
            return _fetchRemote(`${url}prod/feeds/`, {
                page: page
            })
                .then(function (data) {
                    $('.next_publi_link', $answers).remove();

                    $answers.append(data);

                    $answers.find('img.lazyload').lazyload({
                        container: $answers
                    });

                    appEvents.emit('search.doAfterSearch');
                    if (page > 0) {
                        $answers.stop().animate({
                            scrollTop: $answers.scrollTop() + $answers.height()
                        }, 700);
                    }
                    return;
                });
        }
    };


    var publishRecords = function (type, value) {
        var options = {
            lst: '',
            ssel: '',
            act: ''
        };

        switch (type) {
            case 'IMGT':
            case 'CHIM':
                options.lst = value;
                break;

            case 'STORY':
                options.story = value;
                break;
            case 'SSTT':
                options.ssel = value;
                break;
            default:
        }

        $.post(`${url}prod/feeds/requestavailable/`
            , options
            , function (data) {

                return openModal(data);
            });

        return;
    };

    const activatePublicationState = () => {
        appEvents.emit('publication.fetch');
    }

    appEvents.listenAll({
        'publication.activeState': activatePublicationState,
        'publication.fetch': fetchPublications
    });
    return {
        initialize,
        fetchPublications,
        publishRecords,
        openModal
    };
};

export default publication;

