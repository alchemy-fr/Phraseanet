import $ from 'jquery';

const sharingManager = (services, datas, activeTab = false) => {
    const {configService, localeService, toolsStream} = services;
    const url = configService.get('baseUrl');
    let $container = null;
    let dialogParams = {};
    const initialize = (params) => {
        //let {$container, data, tabs, dialogParams} = params;
        $container = params.$container;
        dialogParams = params.dialogParams;
        console.log('>>>>', dialogParams)

        if (params.data.selectionLength === 1) {
            _onUniqueSelection(params.data.databaseId, params.data.records[0].id, params.tabs);
        }
    }

    const _onUniqueSelection = (databaseId, recordId, tabs) => {

        $('#tools-sharing .stateChange_button').bind('click', function (event) {
            const $btn = $(event.currentTarget);
            let state = true;

            // inverse state
            if ($btn.data('state') === 1) {
                state = false;
            }

            // submit changes
            $.post(`tools/sharing-editor/${databaseId}/${recordId}/`, {
                name: $btn.data('name'),
                state: state
            }).done(function (data) {
                // self reload tab with current active tab:
                activeTab = tabs.tabs('option', 'active');
                toolsStream.onNext({
                    action: 'refresh',
                    options: [dialogParams, activeTab]
                });
            }).error(function (err) {
                alert('forbidden action');
            });
            return false;
        });
    };
    return {
        initialize
    }
}

export default sharingManager;
