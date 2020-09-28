require('./thesaurus.scss');

import $ from 'jquery';
import thesaurus from '../../../thesaurus/index';

const workzoneThesaurus = (services) => {
    const { configService, localeService, appEvents } = services;
    let $container = null;
    let thesaurusService = thesaurus(services);
    const initialize = () => {
        $container = $('#thesaurus_tab');
        thesaurusService.initialize({ $container });

        $('#thesaurus_tab .input-medium').on('keyup', function () {
            if ($('#thesaurus_tab .input-medium').val() !== '') {
                $('#thesaurus_tab .th_clear').show();
            } else {
                $('#thesaurus_tab .th_clear').hide();
            }
        });

        $('.th_clear').on('click', function () {
            $('#thesaurus_tab .input-medium').val('');
            $('#thesaurus_tab .gform').submit();
            $('#thesaurus_tab .th_clear').hide();
        });

        $('.treeview>li.expandable>.hitarea').on('click', function () {
            if ($(this).css('background-position') === '99% 22px') {
                $(this).css('background-position', '99% -28px');
                $(this).addClass('active');
            } else {
                $(this).css('background-position', '99% 22px');
                $(this).removeClass('active');
            }
        });
    };

    appEvents.listenAll({
        'thesaurus.show': thesaurusService.show
    });

    return { initialize };
};
export default workzoneThesaurus;
