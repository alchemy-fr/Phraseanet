import $ from 'jquery';
import moveRecords from '../../record/move';
import editRecord from '../../record/edit';
import deleteRecord from '../../record/delete';
import exportRecord from '../../record/export';
import propertyRecord from '../../record/property';
import sharebasketModal from '../../record/sharebasketModal';
import pushbasketModal from '../../record/pushbasketModal';
// import usersListsModal from '../../userslists/index';
import recordPublish from '../../record/publish';
import recordToolsModal from '../../record/tools/index';
import printRecord from '../../record/print';
import bridgeRecord from '../../record/bridge';
import merge from 'lodash.merge';
import * as _ from "underscore";

const toolbar = (services) => {
    const {configService, localeService, appEvents} = services;
    const $container = $('body');
    let workzoneSelection = [];
    let searchSelection = [];

    appEvents.listenAll({
        'broadcast.searchResultSelection': (selection) => {
            searchSelection = selection.serialized;
        },
        'broadcast.workzoneResultSelection': (selection) => {
            workzoneSelection = selection.serialized;
        }
    });

    const initialize = () => {
        _bindEvents();

        return true;
    };

    /**
     * Active group can be a Basket or story
     */
    const _getGroupSelection = (activeGroupId = null) => {
        let $activeGroup = $('.SSTT.active');
        if ($activeGroup.length > 0) {
            activeGroupId = $activeGroup.attr('id').split('_').slice(1, 2).pop();
        }
        return activeGroupId;
    };

    const _getSelection = (from, originalSelection) => {
        let newSelection = {
            list: [],
            group: null, //
            type: null // story | basket
        };
        switch (from) {
            case 'search-result':
                if (searchSelection.length > 0) {
                    newSelection.list = searchSelection;
                } else {
                    newSelection.group = _getGroupSelection();
                }

                break;
            case 'basket':
                if (workzoneSelection.length > 0) {
                    newSelection.list = workzoneSelection;
                } else {
                    newSelection.group = _getGroupSelection();
                    newSelection.type = 'basket';
                }
                break;
            case 'story':
                if (workzoneSelection.length > 0) {
                    newSelection.list = workzoneSelection;
                } else {
                    newSelection.group = _getGroupSelection();
                    newSelection.type = 'story';
                }
                break;
            default:
                newSelection.group = _getGroupSelection();

        }
        //return originalSelection.concat(newSelection);
        return merge({}, originalSelection, newSelection);
    };

    const _prepareParams = (selection) => {
        let params = {};

        if (selection.list.length > 0) {
            params.lst = selection.list;
        }

        if (selection.group !== null) {
            if (selection.type === 'story') {
                params.story = selection.group;
            } else {
                params.ssel = selection.group;
            }
        }

        // require a list of records a basket group or a story
        if (params.lst !== undefined || params.ssel !== undefined || params.story !== undefined) {
            return params;
        }
        return false;
    };

    const _closeActionPanel = () => {
        if($('.tools-accordion').hasClass('active')) {
            $('.rotate').removeClass('down');
            $('.tools-accordion').removeClass('active');
            var panel = $('.tools-accordion').next();
            panel.css('maxHeight', '');
        }
    }
    const _triggerModal = (event, actionFn, needSelectedDocs= true) => {
        event.preventDefault();
        const $el = $(event.currentTarget);
        const selectionSource = $el.data('selection-source');

        let selection = _getSelection(selectionSource, {});
        let params = _prepareParams(selection);

        // require a list of records a basket group or a story
        if (needSelectedDocs && params === false) {
            alert(localeService.t('nodocselected'));
        }
        else {
            return actionFn.apply(null, [params]);
        }
    };

    const _bindEvents = () => {

        /**
         * tools > selection ALL|NONE|per type
         */
        $container.on('click', '.tools .answer_selector', (event) => {
            event.preventDefault();
            let $el = $(event.currentTarget);
            let actionName = $el.data('action-name');
            let state = $el.data('action-state') === true ? true : false;
            let type = $el.data('type');

            switch (actionName) {
                case 'select-toggle':
                    if (state) {
                        appEvents.emit('search.selection.unselectAll');
                    } else {
                        appEvents.emit('search.selection.selectAll');
                    }
                    break;
                case 'select-all':
                    appEvents.emit('search.selection.selectAll');
                    break;
                case 'unselect-all':
                    appEvents.emit('search.selection.unselectAll');
                    break;
                case 'select-type':
                    appEvents.emit('search.selection.selectByType', {type: type});
                    break;
                default:
            }
            $el.data('action-state', !state);
        });

        /**
         * tools > Edit > Move
         */
        $container.on('click', '.TOOL_chgcoll_btn', function (event) {
            //let moveRecordsInstance = moveRecords(services);
            _triggerModal(event, moveRecords(services).openModal);
        });

        /**
         * tools > Edit > Properties
         */
        $container.on('click', '.TOOL_chgstatus_btn', function (event) {
            _triggerModal(event, propertyRecord(services).openModal);
        });

        /**
         * tools > Push
         */
        $container.on('click', '.TOOL_pushdoc_btn', function (event) {
            _triggerModal(event, pushbasketModal(services).openModal);
        });
        /**
         * tools > Push > Share
         */
        $container.on('click', '.TOOL_sharebasket_btn', function (event) {
            _triggerModal(event, sharebasketModal(services).openModal);
        });

//        /**
//         * tools > Push > UsersLists
//         */
//        $container.on('click', '.TOOL_userslists_btn', function (event) {
//            _triggerModal(event, usersListsModal(services).openModal, false);   // false : allow opening without selection
//        });

        /**
         * workzone (opened basket) > feedback
         */
        $container.on('click', '.feedback-user', function (event) {
            event.preventDefault();
            let $el = $(event.currentTarget);

            sharebasketModal(services).openModal({
                ssel: $el.data('basket-id'),
                feedbackaction: 'adduser'
            });
        });

        /**
         * tools > Tools
         */
        $container.on('click', '.TOOL_imgtools_btn', function (event) {
            _triggerModal(event, recordToolsModal(services).openModal);
        });
        /**
         * tools > Export
         */
        $container.on('click', '.TOOL_disktt_btn', function (event) {
            // can't be fully refactored
            _triggerModal(event, exportRecord(services).openModal);
        });
        /**
         * tools > Export > Print
         */
        $container.on('click', '.TOOL_print_btn', function (event) {
            _triggerModal(event, printRecord(services).openModal);
        });
        /**
         * tools > Push > Bridge
         */
        $container.on('click', '.TOOL_bridge_btn', function (event) {
            _triggerModal(event, bridgeRecord(services).openModal);
        });
        /**
         * tools > Push > Publish
         */
        $container.on('click', '.TOOL_publish_btn', function (event) {
            _triggerModal(event, recordPublish(services).openModal);
        });
        /**
         * tools > Delete
         */
        $container.on('click', '.TOOL_trash_btn', function (event) {
            _triggerModal(event, deleteRecord(services).openModal);
        });
        /**
         * tools > Edit
         */
        $container.on('click', '.TOOL_ppen_btn', function (event) {
            _triggerModal(event, editRecord(services).openModal);
        });
        /**
         * tools > Delete Selection
         */
        $container.on('click', '.TOOL_delete_selection_btn', function (event) {
            var $diapoContainer = $(this.closest('.content'));
            _.each($diapoContainer.find('.diapo.selected'), function(item) {
                $(item).find('.WorkZoneElementRemover').trigger('click');
            });
        });

        /**
         * tools-accordion function
         */
        $container.on('click', '.tools-accordion', function (event) {
            $('.rotate').toggleClass("down");
            this.classList.toggle("active");

            /* Toggle between hiding and showing the active panel */
            const panel = this.nextElementSibling; // risky don't change html !
            if (panel.style.maxHeight){
                panel.style.maxHeight = null;
            } else {
                panel.style.maxHeight = panel.scrollHeight + "px";
            }
        });

        $container.on('click', function (event) {
            if (!$(event.target).is('button.tools-accordion')) {
                _closeActionPanel();
            }
        });

        // for facets filter under the search form
        $container.find('#facet_filter_in_search').on('mouseenter', '.facetFilter_AND',function () {
            $(this).find('.buttons-span').show()
        });

        $container.find('#facet_filter_in_search').on('mouseleave', '.facetFilter_AND',function () {
            $(this).find('.buttons-span').hide()
        });

        $container.find('#facet_filter_in_search').on('mouseenter', '.facetFilter_EXCEPT',function () {
            $(this).find('.buttons-span').show()
        });

        $container.find('#facet_filter_in_search').on('mouseleave', '.facetFilter_EXCEPT',function () {
            $(this).find('.buttons-span').hide()
        });

        $container.find('#facet_filter_in_search').on('click', '.facetFilter-closer',function (event) {
            event.stopPropagation();
            let $facet = $(this).parent().parent();
            let facetField = $facet.data('facetField');
            let facetLabel = $facet.data('facetLabel');
            let facetNegated = $facet.data('facetNegated');

            // get the selectedFacets from the facets module
            let selectedFacets = {};
            appEvents.emit('facets.getSelectedFacets', function(v) {
                selectedFacets = v;
            });

            selectedFacets[facetField].values = _.reject(selectedFacets[facetField].values, function (facetValue) {
                return (facetValue.value.label == facetLabel && facetValue.negated == facetNegated);
            });

            // restore the selected facets
            appEvents.emit('facets.setSelectedFacets', selectedFacets);

            appEvents.emit('search.doRefreshState');
            return false;
        });

        $container.find('#facet_filter_in_search').on('click', '.facetFilter-inverse',function (event) {
            event.stopPropagation();
            let $facet = $(this).parent().parent();
            let facetField = $facet.data('facetField');
            let facetLabel = $facet.data('facetLabel');
            let facetNegated = $facet.data('facetNegated');

            // get the selectedFacets from the facets module
            let selectedFacets = {};
            appEvents.emit('facets.getSelectedFacets', function(v) {
                selectedFacets = v;
            });

            let found = _.find(selectedFacets[facetField].values, function (facetValue) {
                return (facetValue.value.label == facetLabel && facetValue.negated == facetNegated);
            });

            if (found) {
                let s_class = "facetFilter" + '_' + (found.negated ? "EXCEPT" : "AND");
                $facet.removeClass(s_class);
                found.negated = !found.negated;
                s_class = "facetFilter" + '_' + (found.negated ? "EXCEPT" : "AND");
                $facet.addClass(s_class);

                // restore the selected facets
                appEvents.emit('facets.setSelectedFacets', selectedFacets);

                appEvents.emit('search.doRefreshState');
            }
            return false;
        });

    };

    return {initialize};
};

export default toolbar;
