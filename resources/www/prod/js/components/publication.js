var publicationModule = (function () {

    var ajaxState = {
        query: null,
        isRunning: false
    };

    var curPage;
    var $answers = $('#answers');

    // refresh current view
    $answers.on('click', '.feed_reload', function (event) {
        event.preventDefault();
        fetchPublications(curPage)
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

                    $answers.find("img.lazyload").lazyload({
                        container: $answers
                    });
                }
                else {
                    $('.see_more.loading', $answers).remove();
                    $answers.append(data);

                    $answers.find("img.lazyload").lazyload({
                        container: $answers
                    });

                    if (!noScroll) {
                        $answers.animate({
                            'scrollTop': ($answers.scrollTop() + $answers.innerHeight() - 80)
                        });
                    }
                }
                searchModule.afterSearch();
            });

    });

    // subscribe_rss
    $answers.on('click', '.subscribe_rss', function (event) {
        event.preventDefault();
        var $this = $(this);

        if (typeof(renew) === 'undefined')
            renew = 'false';
        else
            renew = renew ? 'true' : 'false';

        var buttons = {};
        buttons[language.renewRss] = function () {
            $this.trigger({
                type: 'click',
                renew: true
            });
        };
        buttons[language.fermer] = function () {
            $('#DIALOG').empty().dialog('destroy');
        };

        event.stopPropagation();

        $.ajax({
            type: "GET",
            url: $this.attr('href') + (event.renew === true ? '?renew=true' : ''),
            dataType: 'json',
            success: function (data) {
                if (data.texte !== false && data.titre !== false) {
                    if ($("#DIALOG").data("ui-dialog")) {
                        $("#DIALOG").dialog('destroy');
                    }
                    $("#DIALOG").attr('title', data.titre)
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
                            }
                        }).dialog('open');

                }
            }
        });
    });

    // edit a feed
    $answers.on('click', '.feed .entry a.feed_edit', function () {
        var $this = $(this);
        $.ajax({
            type: "GET",
            url: $this.attr('href'),
            dataType: 'html',
            success: function (data) {
                return _createPublicationModal(data);
            }
        });
        return false;
    });

    // remove a feed
    $answers.on('click', '.feed .entry a.feed_delete', function () {
        if (!confirm('etes vous sur de vouloir supprimer cette entree ?'))
            return false;
        var $this = $(this);
        $.ajax({
            type: "POST",
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
                }
                else
                    alert(data.message);
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
        $see_more = $(this).closest('.see_more');
        $see_more.addClass('loading');
    });


    var _fetchRemote = function (url, data) {
        var page = 0;
        if (data.page === undefined) {
            page = data.page;
        }

        return ajaxState.query = $.ajax({
            type: "GET",
            url: url,
            dataType: 'html',
            data: data,
            beforeSend: function () {
                if (ajaxState.isRunning && ajaxState.query.abort)
                    answAjax.abort();
                if (page === 0)
                    searchModule.clearAnswers();
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
    };

    var _createPublicationModal = function (data) {

        var buttons = {};
        buttons[language.valider] = function () {
            var dialog = p4.Dialog.get(1);
            var error = false;
            var $form = $('form.main_form', dialog.getDomElement());

            $('.required_text', $form).each(function (i, el) {
                if ($.trim($(el).val()) === '') {
                    $(el).addClass('error');
                    error = true;
                }
            });

            if (error) {
                alert(language.feed_require_fields);
            }

            if ($('input[name="feed_id"]', $form).val() === '') {
                alert(language.feed_require_feed);
                error = true;
            }

            if (error) {
                return false;
            }

            $.ajax({
                type: 'POST',
                url: $form.attr('action'),
                data: $form.serializeArray(),
                dataType: 'json',
                beforeSend: function () {
                    $('button', dialog.getDomElement()).prop('disabled', true);
                },
                error: function () {
                    $('button', dialog.getDomElement()).prop('disabled', false);
                },
                timeout: function () {
                    $('button', dialog.getDomElement()).prop('disabled', false);
                },
                success: function (data) {
                    $('button', dialog.getDomElement()).prop('disabled', false);
                    if (data.error === true) {
                        alert(data.message);
                        return;
                    }

                    if ($('form.main_form', dialog.getDomElement()).hasClass('entry_update')) {
                        var id = $('form input[name="entry_id"]', dialog.getDomElement()).val();
                        var container = $('#entry_' + id);

                        container.replaceWith(data.datas);

                        container.hide().fadeIn();

                        $answers.find("img.lazyload").lazyload({
                            container: $answers
                        });
                    }

                    p4.Dialog.Close(1);
                }
            });
            p4.Dialog.Close(1);
        };

        var dialog = p4.Dialog.Create({
            size: 'Full',
            closeOnEscape: true,
            closeButton: true,
            buttons: buttons
        });

        dialog.setContent(data);

        var $feeds_item = $('.feeds .feed', dialog.getDomElement());
        var $form = $('form.main_form', dialog.getDomElement());

        $feeds_item.bind('click', function () {
            $feeds_item.removeClass('selected');
            $(this).addClass('selected');
            $('input[name="feed_id"]', $form).val($('input', this).val());
        }).hover(function () {
            $(this).addClass('hover');
        }, function () {
            $(this).removeClass('hover');
        });

        $form.bind('submit', function () {
            return false;
        });

        return;
    };

    var fetchPublications = function (page) {
        curPage = page;
        return _fetchRemote('../prod/feeds/', {
            page: page
        })
            .then(function (data) {
                $('.next_publi_link', $answers).remove();

                $answers.append(data);

                $answers.find("img.lazyload").lazyload({
                    container: $answers
                });

                searchModule.afterSearch();
                if (page > 0) {
                    $answers.stop().animate({
                        scrollTop: $answers.scrollTop() + $answers.height()
                    }, 700);
                }
                return;
            });
    };


    var publishRecords = function (type, value) {
        var options = {
            lst: '',
            ssel: '',
            act: ''
        };

        switch (type) {
            case "IMGT":
            case "CHIM":
                options.lst = value;
                break;

            case "STORY":
                options.story = value;
                break;
            case "SSTT":
                options.ssel = value;
                break;
        }

        $.post("../prod/feeds/requestavailable/"
            , options
            , function (data) {

                return _createPublicationModal(data);
            });

        return;
    };

    return {
        fetchPublications: function (page) {
            return fetchPublications(page);
        },
        publishRecords: function (type, value) {
            return publishRecords(type, value);
        },
        openModal: function(data) {
            return _createPublicationModal(data);
        }
    };
})();
