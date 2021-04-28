import $ from 'jquery';
import * as appCommons from './../../phraseanet-common';

let highlight = require('imports-loader?$=jquery!../utils/jquery-plugins/highlight');
let colorpicker = require('imports-loader?$=jquery!../utils/jquery-plugins/colorpicker/colorpicker');
const preferences = services => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    const initialize = (options = {}) => {
        const { $container } = options;

        render();

        $container.on('change', '#ADVSRCH_FILTER_FACET', event => {
            let $el = $(event.currentTarget);
            event.preventDefault();
            appCommons.userModule.setPref('facet', $el.prop('checked'));
            appEvents.emit('search.updateFacetData');
            appEvents.emit('search.doRefreshState');
        });

        $container.on('change', '#ADVSRCH_UNSET_FIELD_FACET', event => {
            let $el = $(event.currentTarget);
            event.preventDefault();
            appCommons.userModule.setPref('show_unset_field_facet', $el.prop('checked') ? '1' : '0');
            appEvents.emit('search.updateFacetData');
            appEvents.emit('search.doRefreshState');
        });

        $container.on('click', '.open-preferences', event => {
            event.preventDefault();
            openModal(event);
        });

        $container.on('click', '.preferences-options-submit', event => {
            event.preventDefault();
            submitState();
        });

        $container.on('change', '.preferences-options-start-page', event => {
            event.preventDefault();
            setInitialStateOptions();
        });

        $container.on('change', '.preferences-options-search-reload', event => {
            let $el = $(event.currentTarget);
            event.preventDefault();
            appCommons.userModule.setPref(
                'advanced_search_reload',
                $el.prop('checked') ? '1' : '0'
            );
        });

        $container.on('change', '.preferences-options-use-truncation', event => {
            let $el = $(event.currentTarget);
            event.preventDefault();
            appCommons.userModule.setPref(
                'use_truncation',
                $el.prop('checked') ? '1' : '0'
            );
        });

        $container.on(
            'change',
            '.preferences-options-presentation-thumbnail',
            event => {
                let $el = $(event.currentTarget);
                event.preventDefault();
                appCommons.userModule.setPref('view', $el.val());
                appEvents.emit('search.doRefreshState');
            }
        );

        $container.on(
            'change',
            '.preferences-options-presentation-list',
            event => {
                let $el = $(event.currentTarget);
                event.preventDefault();
                appCommons.userModule.setPref('view', $el.val());
                appEvents.emit('search.doRefreshState');
            }
        );

        $container.on(
            'change',
            '.preferences-options-rollover-caption',
            event => {
                let $el = $(event.currentTarget);
                event.preventDefault();
                appCommons.userModule.setPref('rollover_thumbnail', $el.val());
                appEvents.emit('search.doRefreshState');
            }
        );

        $container.on(
            'change',
            '.preferences-options-rollover-preview',
            event => {
                let $el = $(event.currentTarget);
                event.preventDefault();
                appCommons.userModule.setPref('rollover_thumbnail', $el.val());
                appEvents.emit('search.doRefreshState');
            }
        );

        $container.on(
            'change',
            '.preferences-options-technical-display',
            event => {
                let $el = $(event.currentTarget);
                event.preventDefault();
                appCommons.userModule.setPref('technical_display', $el.val());
                appEvents.emit('search.doRefreshState');
            }
        );

        $container.on(
            'change',
            '.preferences-options-doctype-display',
            event => {
                let $el = $(event.currentTarget);
                event.preventDefault();
                appCommons.userModule.setPref(
                    'doctype_display',
                    $el.prop('checked') ? '1' : '0'
                );
                appEvents.emit('search.doRefreshState');
            }
        );

        $container.on('change', '.preferences-options-basket-status', event => {
            let $el = $(event.currentTarget);
            event.preventDefault();
            appCommons.userModule.setPref(
                'basket_status_display',
                $el.prop('checked') ? '1' : '0'
            );
        });

        $container.on(
            'change',
            '.preferences-options-basket-caption',
            event => {
                let $el = $(event.currentTarget);
                event.preventDefault();
                appCommons.userModule.setPref(
                    'basket_caption_display',
                    $el.prop('checked') ? '1' : '0'
                );
            }
        );

        $container.on('change', '.preferences-options-basket-title', event => {
            let $el = $(event.currentTarget);
            event.preventDefault();
            appCommons.userModule.setPref(
                'basket_title_display',
                $el.prop('checked') ? '1' : '0'
            );
        });


        $container.on('change', '.preferences-options-basket-type', event => {
            let $el = $(event.currentTarget);
            event.preventDefault();
            appCommons.userModule.setPref(
                'basket_type_display',
                $el.prop('checked') ? '1' : '0'
            );
        });

        $container.on('click', '.preference-change-theme-action', event => {
            let $el = $(event.currentTarget);
            let color = $el.data('theme');
            let minified = configService.get('debug') ? '' : '.min';
            // setCss()
            $('#skinCss').attr(
                'href',
                `/assets/production/skin-${color}${minified}.css`
            );

           /* $.post(`${configService.get('baseUrl')}/user/preferences/`, {
                prop: 'css',
                value: color,
                t: Math.random()
            });*/
            var skin = '';
            $.ajax({
                type: 'POST',
                url: `${url}user/preferences/`,
                data: {
                    prop: 'css',
                    value: color,
                    t: Math.random()
                },
                success: function (data) {
                    $('body').removeClass().addClass('PNB ' + color);
                   /* console.log('saved:' + color);*/
                    return;
                }
            });

        });

        $container.on('change', '.preferences-options-collection-order', event => {
            let el = $('#look_box_settings select[name=orderByName]');
            event.preventDefault();
            appCommons.userModule.setPref(
                'order_collection_by',
                el.val()
            );
        });

        $('.preferences-facet-order').change( function (event) {
            let el = $('.look_box_settings select[name=orderFacet]');
            event.preventDefault();
            appCommons.userModule.setPref(
                'order_facet',
                el.val()
            );
            appEvents.emit('search.updateFacetData');
        });

        $('.preferences-facet-values-order').change( function (event) {
            let el = $('.look_box_settings select[name=facetValuesOrder]');
            event.preventDefault();
            appCommons.userModule.setPref(
                'facet_values_order',
                el.val()
            );
            appEvents.emit('search.updateFacetData');
        });

        $container.on('change', '.upload-options-collection', event => {
            let el = $('.settings-box select[name=base_id]');
            event.preventDefault();
            appCommons.userModule.setPref(
                'upload_last_used_collection',
                el.val()
            );
        });

        $('#nperpage_slider').slider({
            value: parseInt($('#nperpage_value').val(), 10),
            min: 10,
            max: 100,
            step: 10,
            slide: function (event, ui) {
                $('#nperpage_value').val(ui.value);
            },
            stop: function (event, ui) {
                appCommons.userModule.setPref(
                    'images_per_page',
                    $('#nperpage_value').val()
                );
            }
        });
        $('#sizeAns_slider').slider({
            value: parseInt($('#sizeAns_value').val(), 10),
            min: 90,
            max: 270,
            step: 10,
            slide: function (event, ui) {
                $('#sizeAns_value').val(ui.value);
            },
            stop: function (event, ui) {
                appCommons.userModule.setPref(
                    'images_size',
                    $('#sizeAns_value').val()
                );
            }
        });
        $('#backcolorpickerHolder').ColorPicker({
            flat: true,
            color: '404040',
            livePreview: false,
            eventName: 'mouseover',
            onSubmit: function (hsb, hex, rgb, el) {
                var back_hex = '';
                var unactive = '';
                var sim_b;

                if (hsb.b >= 50) {
                    back_hex = '000000';

                    sim_b = 0.1 * hsb.b;
                } else {
                    back_hex = 'FFFFFF';

                    sim_b = 100 - 0.1 * (100 - hsb.b);
                }

                sim_b = 0.1 * hsb.b;

                var sim_rgb = appCommons.utilsModule.hsl2rgb(
                    hsb.h,
                    hsb.s,
                    sim_b
                );
                var sim_hex = appCommons.utilsModule.RGBtoHex(
                    sim_rgb.r,
                    sim_rgb.g,
                    sim_rgb.b
                );

                appCommons.userModule.setPref('background-selection', hex);
                appCommons.userModule.setPref(
                    'background-selection-disabled',
                    sim_hex
                );
                appCommons.userModule.setPref('fontcolor-selection', back_hex);

                $('style[title=color_selection]').empty();

                var datas =
                    '.diapo.selected,#reorder_box .diapo.selected, #EDIT_ALL .diapo.selected, .list.selected, .list.selected .diapo' +
                    '{' +
                    '    COLOR: #' +
                    back_hex +
                    ';' +
                    '    BACKGROUND-COLOR: #' +
                    hex +
                    ';' +
                    '}';
                $('style[title=color_selection]').empty().text(datas);
            }
        });
        $('#backcolorpickerHolder')
            .find('.colorpicker_submit')
            .append($('#backcolorpickerHolder .submiter'))
            .bind('click', function () {
                $(this).highlight('#CCCCCC');
            });
        $('#look_box .tabs').tabs();
    };

    const render = () => {
        let availableThemes = configService.get('availableThemes');
        let themeTpl = '';

        for (let t in availableThemes) {
            let curTheme = availableThemes[t];
            themeTpl += `<div class="colorpicker_box preference-change-theme-action" data-theme="${curTheme.name}" style="width:16px;height:16px;background-color:#${curTheme.name};">&nbsp;</div>`;
        }
        // generates themes
        $('#theme-container').empty().append(themeTpl);
    };

    // look_box
    function setInitialStateOptions() {
        var el = $('#look_box_settings select[name=start_page]');

        switch (el.val()) {
            case 'LAST_QUERY':
            case 'PUBLI':
            case 'HELP':
                $('#look_box_settings input[name=start_page_value]').hide();
                break;
            case 'QUERY':
                $('#look_box_settings input[name=start_page_value]').show();
                break;
            default:
        }
    }

    function submitState() {
        var el = $('#look_box_settings select[name=start_page]');
        var val = el.val();

        var start_page_query = $(
            '#look_box_settings input[name=start_page_value]'
        ).val();

        if (val === 'QUERY') {
            appCommons.userModule.setPref('start_page_query', start_page_query);
        }

        appCommons.userModule.setPref('start_page', val);
    }

    function openModal(event) {
        $('#look_box')
            .dialog({
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
            })
            .dialog('open');
    }

    return {
        initialize,
        openModal
    };
};

export default preferences;
