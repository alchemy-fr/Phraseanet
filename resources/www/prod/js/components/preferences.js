var p4 = p4 || {};

var preferencesModule = (function (p4) {
    function initLook() {
        $('#nperpage_slider').slider({
            value: parseInt($('#nperpage_value').val()),
            min: 10,
            max: 100,
            step: 10,
            slide: function (event, ui) {
                $('#nperpage_value').val(ui.value);
            },
            stop: function (event, ui) {
                userModule.setPref('images_per_page', $('#nperpage_value').val());
            }
        });
        $('#sizeAns_slider').slider({
            value: parseInt($('#sizeAns_value').val()),
            min: 90,
            max: 270,
            step: 10,
            slide: function (event, ui) {
                $('#sizeAns_value').val(ui.value);
            },
            stop: function (event, ui) {
                userModule.setPref('images_size', $('#sizeAns_value').val());
            }
        });
    }

    // look_box
    function setInitialStateOptions() {
        var el = $('#look_box_settings select[name=start_page]');

        switch (el.val()) {
            case "LAST_QUERY":
            case "PUBLI":
            case "HELP":
                $('#look_box_settings input[name=start_page_value]').hide();
                break;
            case "QUERY":
                $('#look_box_settings input[name=start_page_value]').show();
                break;
        }
    }

    function setInitialState() {
        var el = $('#look_box_settings select[name=start_page]');
        var val = el.val();


        var start_page_query = $('#look_box_settings input[name=start_page_value]').val();

        if (val === 'QUERY') {
            userModule.setPref('start_page_query', start_page_query);
        }

        userModule.setPref('start_page', val);

    }


    function lookBox(el, event) {
        $("#look_box").dialog({
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
        initLook: initLook,
        lookBox: lookBox,
        setInitialStateOptions: setInitialStateOptions,
        setInitialState: setInitialState
    };
}(p4));
