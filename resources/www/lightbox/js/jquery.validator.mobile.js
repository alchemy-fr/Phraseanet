$(document).ready(function () {
    if (typeof validator_loaded === 'boolean')
        return;

    display_basket();
    $('body').on('touchstart click', '.confirm_report', function (event) {
        event.preventDefault();
        var $this = $(this);

        $('.loader', $this).css({
            visibility: 'visible'
        });

        $.ajax({
            type: "POST",
            url: "/lightbox/ajax/SET_RELEASE/" + $('#basket_validation_id').val() + "/",
            dataType: 'json',
            error: function (data) {
                $('.loader', $this).css({
                    visibility: 'hidden'
                });
            },
            timeout: function (data) {
                $('.loader', $this).css({
                    visibility: 'hidden'
                });
            },
            success: function (data) {
                $('.loader', $this).css({
                    visibility: 'hidden'
                });
                if (data.datas) {
                    alert(data.datas);
                }
                if (!data.error) {
                    releasable = false;
                }

                return;
            }
        });
        return false;
    });

    $('body').on('touchstart click', '.agreement_radio', function (event) {
        var sselcont_id = $(this).attr('for').split('_').pop();
        var agreement = $('#' + $(this).attr('for')).val() == 'yes' ? '1' : '-1';

        $.mobile.loading();

        $.ajax({
            type: "POST",
            url: "/lightbox/ajax/SET_ELEMENT_AGREEMENT/" + sselcont_id + "/",
            dataType: 'json',
            data: {
                agreement: agreement
            },
            error: function (datas) {
                alert('error');
                $.mobile.loading();
            },
            timeout: function (datas) {
                alert('error');
                $.mobile.loading();
            },
            success: function (datas) {
                if (!datas.error) {
                    if (agreement == '1')
                        $('.valid_choice_' + sselcont_id).removeClass('disagree').addClass('agree');
                    else
                        $('.valid_choice_' + sselcont_id).removeClass('agree').addClass('disagree');
                    $.mobile.loading();
                    if (datas.error) {
                        alert(datas.datas);
                        return;
                    }

                    releasable = datas.release;
                }
                else {
                    alert(datas.datas);
                }
                return;
            }
        });
        //return false;

    });

    $('body').on('touchstart click', '.note_area_validate', function (event) {
        var sselcont_id = $(this).closest('form').find('input[name="sselcont_id"]').val();

        $.mobile.loading();
        $.ajax({
            type: "POST",
            url: "/lightbox/ajax/SET_NOTE/" + sselcont_id + "/",
            dataType: 'json',
            data: {
                note: $('#note_form_' + sselcont_id).find('textarea').val()
            },
            error: function (datas) {
                alert('error');
                $.mobile.loading();
            },
            timeout: function (datas) {
                alert('error');
                $.mobile.loading();
            },
            success: function (datas) {
                $.mobile.loading();
                if (datas.error) {
                    alert(datas.datas);
                    return;
                }

                $('#notes_' + sselcont_id).empty().append(datas.datas);
                window.history.back();
                return;
            }
        });
        return false;
    });
    function load_report() {
        $.ajax({
            type: "GET",
            url: "/lightbox/ajax/LOAD_REPORT/" + $('#navigation').val() + "/",
            dataType: 'html',
            success: function (data) {
                $('#report_list_backup').empty().append($('#report').html());
                $('#report').empty().append(data);

                return;
            }
        });

    }

    function display_basket() {
        var sc_wrapper = $('#sc_wrapper');
        var basket_options = $('#basket_options');

        $('#report_summary').on('click',function () {
            load_report();
            $(this).hide();
            $('#report_list').show();
            return false;
        }).addClass('clickable');

        $('#report_list').on('click',function () {
            $(this).hide();
            $('#report_summary').show();
            $('#report').empty().append($('#report_list_backup').html());
            return false;
        }).addClass('clickable');

        $('.basket_element', sc_wrapper).parent()
            .bind('click', function (event) {
                scid_click(event, this);
                adjust_visibility(this);
                return false;
            });

        $('.agree_button, .disagree_button', sc_wrapper).bind('click',function (event) {

            var sselcont_id = $(this).closest('.basket_element').attr('id').split('_').pop();

            var agreement = $(this).hasClass('agree_button') ? '1' : '-1';

            set_agreement(event, $(this), sselcont_id, agreement);
            return false;
        }).addClass('clickable');

        n = $('.basket_element', sc_wrapper).length;
        $('#sc_container').width(n * $('.basket_element_wrapper:first', sc_wrapper).outerWidth() + 1);

    }

    validator_loaded = true;
});
