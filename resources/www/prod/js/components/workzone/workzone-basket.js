var p4 = p4 || {};

var workzoneBasketModule = (function (p4) {

    function archiveBasket(basket_id) {
        $.ajax({
            type: "POST",
            url: "../prod/baskets/" + basket_id + "/archive/?archive=1",
            dataType: 'json',
            beforeSend: function () {

            },
            success: function (data) {
                if (data.success) {
                    var basket = $('#SSTT_' + basket_id);
                    var next = basket.next();

                    if (next.data("ui-droppable")) {
                        next.droppable('destroy');
                    }

                    next.slideUp().remove();

                    if (basket.data("ui-droppable")) {
                        basket.droppable('destroy');
                    }

                    basket.slideUp().remove();

                    if ($('#baskets .SSTT').length === 0) {
                        return p4.WorkZone.refresh(false);
                    }
                }
                else {
                    alert(data.message);
                }
                return;
            }
        });
    }

    function deleteBasket(item) {
        if ($("#DIALOG").data("ui-dialog")) {
            $("#DIALOG").dialog('destroy');
        }

        var k = $(item).attr('id').split('_').slice(1, 2).pop();	// id de chutier
        $.ajax({
            type: "POST",
            url: "../prod/baskets/" + k + '/delete/',
            dataType: 'json',
            beforeSend: function () {

            },
            success: function (data) {
                if (data.success) {
                    var basket = $('#SSTT_' + k);
                    var next = basket.next();

                    if (next.data("ui-droppable")) {
                        next.droppable('destroy');
                    }

                    next.slideUp().remove();

                    if (basket.data("ui-droppable")) {
                        basket.droppable('destroy');
                    }

                    basket.slideUp().remove();

                    if ($('#baskets .SSTT').length === 0) {
                        return p4.WorkZone.refresh(false);
                    }
                }
                else {
                    alert(data.message);
                }
                return;
            }
        });
    }

    function openBasketPreferences() {
        $('#basket_preferences').dialog({
            closeOnEscape: true,
            resizable: false,
            width: 450,
            height: 500,
            modal: true,
            draggable: false,
            overlay: {
                backgroundColor: '#000',
                opacity: 0.7
            }
        }).dialog('open');
    }



    return {
        archiveBasket: archiveBasket,
        deleteBasket: deleteBasket,
        openBasketPreferences: openBasketPreferences

    }
})(p4);