import $ from 'jquery';

// poll notification is now from menu bar
// so this is never called
const basket = () => {

    const onUpdatedContent = (data) => {

        if (data.changed.length > 0) {
            let current_open = $('.SSTT.ui-state-active');
            let main_open = false;
            for (let i = 0; i !== data.changed.length; i++) {
                var sstt = $('#SSTT_' + data.changed[i]);
                if (sstt.size() === 0) {
                    if (main_open === false) {
                        $('#baskets .bloc').animate({top: 30}, function () {
                            $('#baskets .alert_datas_changed:first').show();
                        });
                        main_open = true;
                    }
                } else {
                    if (!sstt.hasClass('active')) {
                        sstt.addClass('unread');
                    } else {
                        $('.alert_datas_changed', $('#SSTT_content_' + data.changed[i])).show();
                    }
                }
            }
        }
    };


    const subscribeToEvents = {
        'notification.refresh': onUpdatedContent
    };

    return {subscribeToEvents};
};

export default basket;
