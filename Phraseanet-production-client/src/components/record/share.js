import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
const shareRecord = (services) => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let $container = null;
    const initialize = (options) => {
        let {$container} = options;
        $container.on('click', '.share-record-action', function (event) {
            event.preventDefault();
            let $el = $(event.currentTarget);
            let db = $el.data('db');
            let recordId = $el.data('record-id');

            doShare(db, recordId);
        });
    };

    const doShare = (bas, rec) => {
        var $dialog = dialog.create(services, {
            size: 'Medium',
            title: localeService.t('share')
        });

        $.ajax({
            type: 'GET',
            url: `${url}prod/share/record/${bas}/${rec}/`,
            //dataType: 'html',
            success: function (data) {
                $dialog.setContent(data);
                _onShareReady();
            }
        });

        return true;
    };

    const _onShareReady = () => {
        $('input.ui-state-default').hover(
            function () {
                $(this).addClass('ui-state-hover');
            },
            function () {
                $(this).removeClass('ui-state-hover');
            }
        );

        $('#permalinkUrlCopy').on('click', function (event) {
            event.preventDefault();
            return copyElContentClipboard('permalinkUrl');
        });

        $('#permaviewUrlCopy').on('click', function (event) {
            event.preventDefault();
            return copyElContentClipboard('permaviewUrl');
        });

        $('#embedCopy').on('click', function (event) {
            event.preventDefault();
            return copyElContentClipboard('embedRecordUrl');
        });

        var copyElContentClipboard = function (elId) {
            var copyEl = document.getElementById(elId);
            copyEl.select();
            try {
                var successful = document.execCommand('copy');
                var msg = successful ? 'successful' : 'unsuccessful';
            } catch (err) {
                console.log('unable to copy');
            }
        }
    }

    return {initialize};
};

export default shareRecord;
