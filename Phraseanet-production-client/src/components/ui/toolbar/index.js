import $ from 'jquery';
import moveRecords from '../../record/move';
import editRecord from '../../record/edit';
import deleteRecord from '../../record/delete';
import exportRecord from '../../record/export';
import propertyRecord from '../../record/property';
import recordPushModal from '../../record/push';
import recordPublish from '../../record/publish';
import recordToolsModal from '../../record/tools/index';
import printRecord from '../../record/print';
import recordFeedbackModal from '../../record/feedback';
import bridgeRecord from '../../record/bridge';
import videoToolsModal from '../../record/videoEditor/index';
import merge from 'lodash.merge';

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
    const _triggerModal = (event, actionFn, nodocselected= true) => {
        event.preventDefault();
        const $el = $(event.currentTarget);
        const selectionSource = $el.data('selection-source');

        let selection = _getSelection(selectionSource, {});
        let params = _prepareParams(selection);

        // require a list of records a basket group or a story
        if (params !== false) {
            return actionFn.apply(null, [params]);
        } else {
            if (nodocselected != false) {
                alert(localeService.t('nodocselected'));
            }
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
         * tools > Edit > VideoEditor
         */
        $container.on('click', '.video-tools-record-action', function (event) {
            _triggerModal(event, videoToolsModal(services).openModal, false);
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
            _triggerModal(event, recordPushModal(services).openModal);
        });
        /**
         * tools > Push > Feedback
         */
        $container.on('click', '.TOOL_feedback_btn', function (event) {

            _triggerModal(event, recordFeedbackModal(services).openModal);
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
            var panel = this.nextElementSibling;
            if (panel.style.maxHeight){
                panel.style.maxHeight = null;
            } else {
                panel.style.maxHeight = panel.scrollHeight + "px";
            }
        });

        $container.on('click', function (event) {
            if ($(event.target).is('button.tools-accordion')) {
                return;
            } else {
                _closeActionPanel();
            }
        });
    };

    return {initialize};
};

export default toolbar;
