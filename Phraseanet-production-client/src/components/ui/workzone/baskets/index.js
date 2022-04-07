import $ from 'jquery';
import deleteBasket from './../../../basket/delete';
import quitshareBasket from './../../../basket/quitshare';
import actionsBasket from './../../../basket/actions';
import archiveBasket from './../../../basket/archive';
import basketCreate from './../../../basket/create';
import storyCreate from './../../../story/create';
import basketUpdate from './../../../basket/update';
import basketBrowse from './../../../basket/browse';
import basketReorderContent from './../../../basket/reorderContent';
import storyReorderContent from './../../../story/reorderContent';
import * as appCommons from './../../../../phraseanet-common';

const workzoneBaskets = (services) => {
    const { configService, localeService, appEvents } = services;


    const initialize = () => {
        deleteBasket(services).initialize();
        quitshareBasket(services).initialize();
        actionsBasket(services).initialize();
        archiveBasket(services).initialize();
        basketCreate(services).initialize();
        storyCreate(services).initialize();
        basketUpdate(services).initialize();
        basketBrowse(services).initialize();
        basketReorderContent(services).initialize();
        storyReorderContent(services).initialize();

        $( window ).on( "load", function() {
            appEvents.emit('workzone.refresh', {
                basketId: 'current',
                sort: 'date'
            });
        });

        $('body').on('click', '.basket-filter-action', (event) => {
                event.preventDefault();
                const $el = $(event.currentTarget);
                if ($el.data('sort') !== '') {
                    appEvents.emit('workzone.refresh', {
                        basketId: 'current',
                        sort: $el.data('sort')
                    });

                    appCommons.userModule.setPref('workzone_order', $el.data('sort'));
                }

            })
            .on('click', '.basket-preferences-action', (event) => {
                event.preventDefault();
                openBasketPreferences();

            });
    };

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

    appEvents.listenAll({
        'baskets.doOpenBasketPreferences': openBasketPreferences
    });


    return {
        initialize,
        openBasketPreferences
    };
};

export default workzoneBaskets;
