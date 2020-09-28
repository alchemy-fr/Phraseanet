import $ from 'jquery';
require('./../../phraseanet-common/components/vendors/contextMenu');

const mainMenu = (services) => {
    const { configService, localeService, appEvents } = services;
    const $container = $('body');

    const initialize = () => {
        _bindEvents();
        return true;
    };

    const _bindEvents = () => {
        /**
         * mainMenu > Publication link
         */
        $container.on('click', '.state-navigation', function (event) {
            event.preventDefault();
            let $el = $(event.currentTarget);

            // @TODO loop through each state args:

            _stateNavigator($el.data('state'));
        });
        /**
         * mainMenu > help context menu
         */

     /*   $('body').on('click', '#help-trigger', function (event) {
            $('#mainMenu .helpcontextmenu').toggleClass('shown');
        });*/
    };

    const _stateNavigator = (...state) => {
        let [stateName, stateArgs] = state;

        switch (stateName) {
            case 'publication':
                appEvents.emit(`${stateName}.activeState`); // fetch
                break;
            default:
                console.log('navigation state error: state "' + stateName + '" not found');
        }

    };
    return { initialize };
};

export default mainMenu;
