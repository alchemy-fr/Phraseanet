import $ from 'jquery';
import _ from 'underscore';
import merge from 'lodash.merge';
import * as appCommons from './../../../phraseanet-common';
import { cleanTags } from '../../utils/utils';
import { sprintf } from 'sprintf-js';
import recordEditorLayout from './layout';
import presetsModule from './presets';
import searchReplace from './plugins/searchReplace';
import preview from './plugins/preview';
import thesaurusDatasource from './plugins/thesaurusDatasource';
import geonameDatasource from './plugins/geonameDatasource';
import leafletMap from './../../geolocalisation/providers/mapbox';
import Emitter from '../../core/emitter';
import RecordCollection from './models/recordCollection';
import FieldCollection from './models/fieldCollection';
import StatusCollection from './models/statusCollection';

require('./../../../phraseanet-common/components/tooltip');
require('./../../../phraseanet-common/components/vendors/contextMenu');

const recordEditorService = services => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let recordEditorEvents;
    let $container = null;
    let options = {};
    let recordConfig = {};
    let ETHSeeker;
    let $editorContainer = null;
    let $ztextStatus;
    let $editTextArea;
    let $editDateArea;
    let $editTimeArea;
    let $editMonoValTextArea;
    let $editMultiValTextArea;
    let $toolsTabs;
    let $idExplain;
    let $dateFormat = /^\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}$|^\d{4}\/\d{2}\/\d{2}$|^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$|^\d{4}-\d{2}-\d{2}$/;

    const initialize = params => {
        let initWith = ({ $container, recordConfig } = params);
        recordEditorEvents = new Emitter();
        options = {};
        $editorContainer = options.$container = $container; //$('#idFrameE');
        options.recordConfig = recordConfig || {};
        options.textareaIsDirty = false;
        options.fieldLastValue = '';
        options.lastClickId = null;
        options.sbas_id = false;
        options.what = false;
        options.newrepresent = false;

        $ztextStatus = $('#ZTextStatus', options.$container);
        $editTextArea = $('#idEditZTextArea', options.$container);
        $editDateArea = $('#idEditZDateArea', options.$container);
        $editTimeArea = $('#idEditTimeArea', options.$container);
        $editMonoValTextArea = $('#ZTextMonoValued', options.$container);
        $editMultiValTextArea = $('#EditTextMultiValued', options.$container);
        $toolsTabs = $('#EDIT_MID_R .tabs', options.$container);
        $idExplain = $('#idExplain', options.$container);

        $toolsTabs.tabs({
            activate: function (event, ui) {
                recordEditorEvents.emit('tabChange', {
                    tab: ui.newPanel.selector
                });
            }
        });
        _bindEvents();
        startThisEditing(recordConfig);
    };

    const _bindEvents = () => {
        onUserInputComplete = _.debounce(onUserInputComplete, 300);

        recordEditorEvents.listenAll({
            'recordEditor.addMultivaluedField': addValueInMultivaluedField,
            'recordEditor.onUpdateFields': refreshFields,
            'recordEditor.submitAllChanges': submitChanges,
            'recordEditor.cancelAllChanges': cancelChanges,

            'recordEditor.addValueFromDataSource': addValueFromDataSource,
            'recordEditor.addPresetValuesFromDataSource': addPresetValuesFromDataSource,
            /* eslint-disable quote-props */
            appendTab: appendTab,
            'recordEditor.activateToolTab': activateToolTab
        });

        // set grouping (regroupement) image
        $editorContainer.parent().on('click', '.set-grouping-image-action', event => {
            let $el = $(event.currentTarget);
            setRegDefault($el.data('index'), $el.data('record-id'));
        })

        $editorContainer
            .on('click', '.select-record-action', event => {
                let $el = $(event.currentTarget);
                _onSelectRecord(event, $el.data('index'));
            })
            // status field edition
            .on('click', '.edit-status-action', event => {
                event.cancelBubble = true;
                event.stopPropagation();

                if (
                    !options.textareaIsDirty ||
                    validateFieldChanges(event, 'ask_ok') === true
                ) {
                    enableStatusField(event);
                }
                return false;
            })
            // edit field by name / set active for edition
            .on('click', '.edit-field-action', event => {
                let $el = $(event.currentTarget);
                if (
                    !options.textareaIsDirty ||
                    validateFieldChanges(event, 'ask_ok') === true
                ) {
                    onSelectField(event, $el.data('id'));
                }
                return false;
            })
            .on('click', '.field-navigate-action', event => {
                event.preventDefault();
                let $el = $(event.currentTarget);
                let dir = $el.data('direction') === 'forward' ? 1 : -1;

                fieldNavigate(event, dir);
            })
            .on('submit', '.add-multivalued-field-action', event => {
                event.preventDefault();
                let $el = $(event.currentTarget);
                let fieldValue = $('#' + $el.data('input-id')).val();

                addValueInMultivaluedField({ value: fieldValue });
            })
            .on('click', '.edit-multivalued-field-action', event => {
                event.preventDefault();
                let $el = $(event.currentTarget);

                _editMultivaluedField($el, $el.data('index'));
            })
            .on('click', '.toggle-status-field-action', event => {
                event.preventDefault();
                let $el = $(event.currentTarget);
                let state = $el.data('state') === true ? 1 : 0;
                toggleStatus(event, $el.data('bit'), state);
            })
            .on('click', '.commit-field-action', event => {
                event.preventDefault();
                let $el = $(event.currentTarget);
                validateFieldChanges(event, $el.data('mode'));
            })
            .on('click', '.apply-multi-desc-action', event => {
                event.preventDefault();
                submitChanges({ event });
            })
            .on('click', '.cancel-multi-desc-action', event => {
                event.preventDefault();
                cancelChanges({ event });
            })
            .on('mouseup mousedown keyup keydown', '#idEditZTextArea', function (
                event
            ) {
                switch (event.type) {
                    case 'mouseup':
                        _onTextareaMouseUp(event);
                        break;
                    case 'mousedown':
                        _onTextareaMouseDown(event);
                        break;
                    case 'keyup':
                        _onTextareaKeyUp(event);
                        break;
                    case 'keydown':
                        _onTextareaKeyDown(event);
                        break;
                    default:
                }
            })
            .on('change mouseup mousedown keyup keydown', '#idEditZDateArea', function (e) {
                let dateText = $(this).val();

                if (dateText !== undefined && dateText.match($dateFormat) !== null) {
                    $editDateArea.css('width',167);
                    $editTimeArea.show();
                } else {
                    $editTimeArea.hide();
                    $editDateArea.css('width',210);
                }

                // format yyyy/mm/dd or yyyy/mm/dd hh:mm:ss or yyyy-mm-dd or yyyy-mm-dd hh:mm:ss
                if (dateText !== undefined && dateText.match($dateFormat) !== null) {
                    options.fieldLastValue = $editDateArea.val();
                    options.textareaIsDirty = true;
                }
            })
            .on('change', '#idEditTimeArea', function (e) {
                let date = $editDateArea.val();
                date = date.split(' ');
                // retrieve the date and add the time to it
                $editDateArea.val(date[0] + ' ' + $(this).val() + ':00');
                let dateText = $editDateArea.val();

                // format yyyy/mm/dd or yyyy/mm/dd hh:mm:ss or yyyy-mm-dd or yyyy-mm-dd hh:mm:ss
                if (dateText !== undefined && dateText.match($dateFormat) !== null) {
                    options.fieldLastValue = $editDateArea.val();
                    options.textareaIsDirty = true;
                } else {
                    $editTimeArea.hide();
                    $editDateArea.css('width',210);
                }
            })
        ;
    };

    const onGlobalKeydown = (event, specialKeyState) => {
        if (specialKeyState === undefined) {
            let specialKeyState = {
                isCancelKey: false,
                isShortcutKey: false
            };
        }
        switch (event.keyCode) {
            case 9: // tab ou shift-tab
                fieldNavigate(
                    event,
                    appCommons.utilsModule.is_shift_key(event) ? -1 : 1
                );
                specialKeyState.isCancelKey = specialKeyState.isShortcutKey = true;
                break;
            case 27:
                cancelChanges({ event });
                specialKeyState.isShortcutKey = true;
                break;

            case 33: // pg up
                if (
                    !options.textareaIsDirty ||
                    validateFieldChanges(event, 'ask_ok')
                ) {
                    skipImage(event, 1);
                }
                specialKeyState.isCancelKey = true;
                break;
            case 34: // pg dn
                if (
                    !options.textareaIsDirty ||
                    validateFieldChanges(event, 'ask_ok')
                ) {
                    skipImage(event, -1);
                }
                specialKeyState.isCancelKey = true;
                break;
            default:
        }
        return specialKeyState;
    };

    function startThisEditing(params) {
        // sbas_id, what, regbasprid, ssel) {
        let {
            hasMultipleDatabases,
            databoxId,
            mode,
            notActionable,
            notActionableMsg,
            state
        } = params;

        if (notActionable > 0) {
            alert(notActionableMsg);
        }

        options.sbas_id = databoxId;
        options.what = mode;
        options = merge(options, state);

        options.fieldCollection = new FieldCollection(state.T_fields);
        options.statusCollection = new StatusCollection(state.T_statbits);
        options.recordCollection = new RecordCollection(
            state.T_records,
            options.fieldCollection,
            options.statusCollection,
            state.T_sgval
        );

        $editMultiValTextArea.bind('keyup', function () {
            _reveal_mval($(this).val());
        });

        $('#divS div.edit_field:odd').addClass('odd');
        $('#divS div')
            .bind('mouseover', function () {
                $(this).addClass('hover');
            })
            .bind('mouseout', function () {
                $(this).removeClass('hover');
            });

        $('#editcontextwrap').remove();

        if ($('#editcontextwrap').length === 0) {
            $('body').append('<div id="editcontextwrap"></div>');
        }

        // if is a group, only select the group
        if (options.what === 'GRP') {
            _toggleGroupSelection();
        } else {
            _edit_select_all();
        }

        /**Edit Story Select all item **/
        $('#select-all-diapo').change(function() {
            if(this.checked) {
                _edit_select_all_right(true);
            }
            else{
                _edit_select_all_right(false);
            }
        });

        $('.previewTips, .DCESTips, .fieldTips', options.$container).tooltip({
            fixable: true,
            fixableIndex: 1200
        });
        $('.infoTips', options.$container).tooltip();

        if (options.what === 'GRP') {
            $('#EDIT_FILM2 .reg_opts').show();

            $.each($('#EDIT_FILM2 .contextMenuTrigger'), function () {
                var id = $(this).attr('id').split('_').slice(1, 3).join('_');
                $(this).contextMenu('#editContext_' + id + '', {
                    appendTo: '#editcontextwrap',
                    openEvt: 'click',
                    dropDown: true,
                    theme: 'vista',
                    showTransition: 'slideDown',
                    hideTransition: 'hide',
                    shadow: false
                });
            });
        }

        $('#idEditDateZone', options.$container).datepicker({
            changeYear: true,
            changeMonth: true,
            dateFormat: 'yy/mm/dd',
            onSelect: function (dateText, inst) {
                var lval = $editDateArea.val();
                if (lval !== dateText) {
                    options.fieldLastValue = lval;
                    $editDateArea.val(dateText);
                    $editDateArea.trigger('keyup.maxLength');
                    options.textareaIsDirty = true;
                    validateFieldChanges(null, 'ok');
                }
            }
        });

        checkRequiredFields();

        try {
            $('#divS .edit_field:first').trigger('mousedown');
        } catch (err) {}

        let recordEditorServices = {
            configService,
            localeService,
            recordEditorEvents
        };

        recordEditorLayout(recordEditorServices).initialize({
            $container: $editorContainer,
            parentOptions: options
        });
        presetsModule(recordEditorServices).initialize({
            $container: $editorContainer,
            parentOptions: options
        });
        // init plugins
        searchReplace(recordEditorServices).initialize({
            $container: $editorContainer,
            parentOptions: options
        });
        preview(recordEditorServices).initialize({
            $container: $('#TH_Opreview .PNB10'),
            parentOptions: options
        });

        geonameDatasource(recordEditorServices).initialize({
            $container: $editorContainer,
            parentOptions: options,
            $editTextArea,
            $editMultiValTextArea
        });

        leafletMap({
            configService,
            localeService,
            eventEmitter: recordEditorEvents
        }).initialize({
            $container: $editorContainer,
            parentOptions: options,
            searchable: true,
            tabOptions: {
                position: 2
            },
            editable: true
        });

        ETHSeeker = thesaurusDatasource(recordEditorServices).initialize({
            $container: $editorContainer,
            parentOptions: options,
            $editTextArea,
            $editMultiValTextArea
        });

        recordEditorEvents.emit('recordSelection.changed', {
            selection: loadSelectedRecords(),
            selectionPos: getRecordSelection()
        });
    }

    function _toggleGroupSelection() {
        var groupIndex = 0;
        _onSelectRecord(false, groupIndex);
    }

    function skipImage(evt, step) {
        let cache = $('#EDIT_FILM2');
        let first = $('.diapo.selected:first', cache);
        let last = $('.diapo.selected:last', cache);
        let sel = $('.diapo.selected', cache);

        sel.removeClass('selected');

        let i =
            step === 1
                ? parseInt(last.attr('pos'), 10) + 1
                : parseInt(first.attr('pos'), 10) - 1;

        if (i < 0) {
            i = parseInt($('.diapo:last', cache).attr('pos'), 10);
        } else if (i >= $('.diapo', cache).length) {
            i = 0;
        }

        _onSelectRecord(evt, i);
    }

    function setRegDefault(n, record_id) {
        options.newrepresent = record_id;

        var src = $('#idEditDiapo_' + n).find('img.edit_IMGT').attr('src');
        var style = $('#idEditDiapo_' + n).find('img.edit_IMGT').attr('style');

        $('#EDIT_GRPDIAPO .edit_IMGT').attr('src', src).attr('style', style);
    }

    // // ---------------------------------------------------------------------------
    // // on change de champ courant
    // // ---------------------------------------------------------------------------

    /**
     * Set a field active by it's meta struct id
     * Open it's editor
     * @param evt
     * @param fieldIndex
     * @private
     */
    function onSelectField(evt, fieldIndex) {
        $editTextArea.blur();
        $editMultiValTextArea.blur();
        $('.editDiaButtons', options.$container).hide();

        $($editTextArea, $editMultiValTextArea).unbind('keyup.maxLength');
        let fields = options.fieldCollection.getFields();
        let field = options.fieldCollection.getFieldByIndex(fieldIndex);

        if (fieldIndex >= 0) {
            // @TODO field edition area should be hooked by plugins

            if (fields === undefined || fields.length === 0) {
                return;
            }

            if (field !== undefined) {
                let name = field.required
                    ? field.label +
                      '<span style="font-weight:bold;font-size:16px;"> * </span>'
                    : field.label;
                $('#idFieldNameEdit', options.$container).html(name);

                let suggestedValuesCollection = options.recordCollection.getFieldSuggestedValues(); //{};

                if (!_.isEmpty(suggestedValuesCollection[fieldIndex])) {
                    var selectElement = $('<select><option selected disabled>' + localeService.t("suggested_values") + '</option> </select>');
                    var selectIdValue = "idSelectSuggestedValues_" + fieldIndex;
                    selectElement.attr('id', selectIdValue);

                    $.each(suggestedValuesCollection[fieldIndex], function (key, value) {
                        var optionElement = $("<option></option>");
                        optionElement.attr("value", key);
                        optionElement.text(key);
                        selectElement.append(optionElement);
                    });

                    selectElement.on('change', function (e) {
                        if (field.multi === true) {
                            $editMultiValTextArea.val($(this).val());
                            $editMultiValTextArea.trigger('keyup.maxLength');
                            addValueInMultivaluedField({
                                value: $editMultiValTextArea.val()
                            });
                        } else {
                            if (appCommons.utilsModule.is_ctrl_key(e)) {
                                var t = $editTextArea.val();
                                $editTextArea.val(t + (t ? ' ; ' : '') + $(this).val());
                            } else {
                                if (field.type === 'date') {
                                    $editDateArea.val($(this).val());
                                } else {
                                    $editTextArea.val($(this).val());
                                }
                            }
                            $editTextArea.trigger('keyup.maxLength');
                            options.textareaIsDirty = true;
                            if (field._status !== 2) {
                                validateFieldChanges(evt, 'ask_ok');
                            }
                        }
                    });

                    $('#idFieldSuggestedValues', options.$container).empty().append(selectElement);

                    $('#idFieldSuggestedValues', options.$container).css('visibility', 'visible');
                    $('.edit-zone-title', options.$container).css('height', 80);
                    $('#EDIT_EDIT', options.$container).css('top', 80);

                } else {
                    $('#idFieldSuggestedValues', options.$container).css('visibility', 'hidden');
                    $('.edit-zone-title', options.$container).css('height', 45);
                    $('#EDIT_EDIT', options.$container).css('top', 45);
                }

                // attachFieldVocabularyAutocomplete
                $($editTextArea, $editMultiValTextArea).autocomplete({
                    minLength: 2,
                    appendTo: '#idEditZone',
                    source: function (request, response) {
                        $.ajax({
                            url: `${url}prod/records/edit/vocabulary/${field.vocabularyControl}/`,
                            dataType: 'json',
                            data: {
                                sbas_id: options.sbas_id,
                                query: request.term
                            },
                            success: function (data) {
                                response(data.results);
                            }
                        });
                    },
                    select: function (event, ui) {
                        addValueInMultivaluedField({
                            value: ui.item.label,
                            vocabularyId: ui.item.id
                        });

                        return false;
                    }
                });

                // attachFieldLengthRestriction
                if (field.maxLength > 0) {
                    $idExplain.html('');

                    $($editTextArea, $editMultiValTextArea)
                        .bind('keyup.maxLength', function (event) {
                            let $this = $(event.currentTarget);
                            var remaining = Math.max(
                                field.maxLength - $(this).val().length,
                                0
                            );
                            $idExplain.html(`
                        <span class='metadatas_restrictionsTips' tooltipsrc="${url}prod/tooltip/metas/restrictionsInfos/${options.sbas_id}/${fieldIndex}/">
                        <img src='/assets/common/images/icons/help32.png' /> Caracteres restants : ${remaining}</span>
                        `);
                            $(
                                '.metadatas_restrictionsTips',
                                $idExplain
                            ).tooltip();
                        })
                        .trigger('keyup.maxLength');
                } else {
                    $idExplain.html('');
                }

                if (!field.multi) {
                    // champ monovalue : textarea
                    $('.editDiaButtons', options.$container).hide();

                    if (field.type === 'date') {
                        $editTextArea.hide();
                        $editDateArea.show();
                        $('#idEditDateZone', options.$container).show();
                        $editDateArea.val(field._value);

                        let dateText= $editDateArea.val();

                        if (dateText === '') {
                            dateText = field._value;
                        }

                        if (dateText !== undefined && dateText.match($dateFormat) !== null) {
                            $editDateArea.css('width',167);
                            $editTimeArea.show();
                        } else {
                            $editTimeArea.hide();
                            $editDateArea.css('width',210);
                        }
                    } else {
                        $editDateArea.hide();
                        $editTimeArea.hide();
                        $('#idEditDateZone', options.$container).hide();
                        $editTextArea.show();
                        $editTextArea.css('height', '100%');
                    }

                    $ztextStatus.hide();
                    $('#ZTextMultiValued', options.$container).hide();
                    $editMonoValTextArea.show();

                    if (field._status === 2) {
                        // heterogene
                        $editTextArea.val((options.fieldLastValue = ''));
                        $editTextArea.addClass('hetero');
                        $('#idDivButtons', options.$container).show(); // valeurs h�t�rog�nes : les 3 boutons remplacer/ajouter/annuler
                    } else {
                        // homogene
                        if (field.type === 'date') {
                            $editDateArea.val(
                                (options.fieldLastValue = field._value)
                            );
                        } else {
                            $editTextArea.val(
                                (options.fieldLastValue = field._value)
                            );
                            $editTextArea.removeClass('hetero');
                        }

                        $('#idDivButtons', options.$container).hide(); // valeurs homog�nes
                        if (field.type === 'date') {
                            let v = field._value.split(' ');
                            let d = v[0].split('/');
                            let dateObj = new Date();
                            if (d.length === 3) {
                                dateObj.setYear(d[0]);
                                dateObj.setMonth(d[1] - 1);
                                dateObj.setDate(d[2]);
                            }

                            if (
                                $('#idEditDateZone', options.$container).data(
                                    'ui-datepicker'
                                )
                            ) {
                                $(
                                    '#idEditDateZone',
                                    options.$container
                                ).datepicker('setDate', dateObj);
                            }
                        }
                    }
                    options.textareaIsDirty = false;

                    $('#idEditZone', options.$container).show();

                    $editTextArea.trigger('keyup.maxLength');

                    self.setTimeout(() => $editTextArea.focus(), 50);
                } else {
                    // champ multivalue : liste
                    $ztextStatus.hide();
                    $editMonoValTextArea.hide();
                    $('#ZTextMultiValued', options.$container).show();

                    $('#idDivButtons', options.$container).hide(); // valeurs homogenes

                    _updateCurrentMval(fieldIndex);

                    $editMultiValTextArea.val('');
                    $('#idEditZone', options.$container).show();

                    $editMultiValTextArea.trigger('keyup.maxLength');

                    self.setTimeout(() => $editMultiValTextArea.focus(), 50);

                    //      reveal_mval();
                }
            }
        } else {
            // pas de champ, masquer la zone du textarea
            $('#idEditZone', options.$container).hide();
            $('.editDiaButtons', options.$container).hide();
        }
        setActiveField(fieldIndex);
    }

    function refreshFields(evt) {
        $('.editDiaButtons', options.$container).hide();

        // initialize values:
        let initializedStatus = options.statusCollection.fillWithRecordValues(
            options.recordCollection.getRecords()
        );

        // tous les statusbits de la base
        for (let statusIndex in initializedStatus) {
            var ck0 = $('#idCheckboxStatbit0_' + statusIndex);
            var ck1 = $('#idCheckboxStatbit1_' + statusIndex);

            switch (initializedStatus[statusIndex]._value) {
                case '0':
                case 0:
                    ck0
                        .removeClass('gui_ckbox_0 gui_ckbox_2')
                        .addClass('gui_ckbox_1');
                    ck1
                        .removeClass('gui_ckbox_1 gui_ckbox_2')
                        .addClass('gui_ckbox_0');
                    break;
                case '1':
                case 1:
                    ck0
                        .removeClass('gui_ckbox_1 gui_ckbox_2')
                        .addClass('gui_ckbox_0');
                    ck1
                        .removeClass('gui_ckbox_0 gui_ckbox_2')
                        .addClass('gui_ckbox_1');
                    break;
                case '2':
                    ck0
                        .removeClass('gui_ckbox_0 gui_ckbox_1')
                        .addClass('gui_ckbox_2');
                    ck1
                        .removeClass('gui_ckbox_0 gui_ckbox_1')
                        .addClass('gui_ckbox_2');
                    break;
                default:
            }
        }

        var nostatus = $('.diapo.selected.nostatus', options.$container).length;
        var status_box = $('#ZTextStatus');
        $('.nostatus, .somestatus, .displaystatus', status_box).hide();

        if (nostatus === 0) {
            $('.displaystatus', status_box).show();
        } else {
            var yesstatus = $('.diapo.selected', options.$container).length;
            if (nostatus === yesstatus) {
                $('.nostatus', status_box).show();
            } else {
                $('.somestatus, .displaystatus', status_box).show();
            }
        }

        populateFields();
        let fieldIndex = options.fieldCollection.getActiveFieldIndex();
        if (fieldIndex === -1) {
            enableStatusField(evt);
        } else {
            onSelectField(evt, fieldIndex);
        }
    }

    /**
     * Populate all fields values from [1-n] records data
     */
    function populateFields() {
        let records = options.recordCollection.getRecords();
        let fields = options.fieldCollection.getFields();
        // tous les champs de la base
        for (let f in fields) {
            let currentField = options.fieldCollection.getFieldByIndex(f);

            currentField._status = 0; // val unknown
            for (let i in records) {
                let currentRecord = options.recordCollection.getRecordByIndex(
                    i
                );
                if (!currentRecord._selected) {
                    continue;
                }

                let v = '';
                if (!currentRecord.fields[f].isEmpty()) {
                    // le champ existe dans la fiche
                    if (currentField.multi) {
                        // champ multi : on compare la concat des valeurs
                        v = currentRecord.fields[f].getSerializedValues();
                    } else {
                        v = currentRecord.fields[f].getValue().getValue();
                    }
                }

                if (currentField._status === 0) {
                    currentField._value = v;
                    currentField._status = 1;
                } else if (
                    currentField._status === 1 &&
                    currentField._value !== v
                ) {
                    currentField._value = '*****';
                    currentField._status = 2;
                    break; // plus la peine de verifier le champ sur les autres records
                }
            }
            var o = document.getElementById('idEditField_' + f);

            if (o) {
                // mixed
                if (currentField._status === 2) {
                    o.innerHTML = "<span class='hetero'>xxxxx</span>";
                } else {
                    var v = currentField._value;
                    v = v instanceof Array ? v.join(';') : v;
                    o.innerHTML = cleanTags(v).replace(
                        /\n/gm,
                        "<span style='color:#0080ff'>&para;</span><br/>"
                    );
                }
            }
            options.fieldCollection.updateField(f, currentField);
        }
    }

    /**
     * enable pseudo-field "status"
     * @param evt
     */
    function enableStatusField(evt) {
        $('.editDiaButtons', options.$container).hide();

        $editTextArea.blur();
        $editMultiValTextArea.blur();

        $('#idFieldNameEdit', options.$container).html('[STATUS]');
        $idExplain.html('&nbsp;');

        $('#ZTextMultiValued', options.$container).hide();
        $editMonoValTextArea.hide();
        $ztextStatus.show();

        $('#idEditZone', options.$container).show();

        document.getElementById('editFakefocus').focus();
        // options.curField = -1;
        setActiveField(-1);
    }

    function _updateCurrentMval(metaStructId, highlightValue, vocabularyId) {
        // on compare toutes les valeurs de chaque fiche selectionnee
        options.T_mval = []; // tab des mots, pour trier
        var a = []; // key : mot ; val : nbr d'occurences distinctes
        var n = 0; // le nbr de records selectionnes

        let records = options.recordCollection.getRecords();
        for (let r in records) {
            let currentRecord = options.recordCollection.getRecordByIndex(r);
            if (!currentRecord._selected) {
                continue;
            }

            currentRecord.fields[metaStructId].sort(_sortCompareMetas);

            var values = currentRecord.fields[metaStructId].getValues();

            for (let v in values) {
                let word = values[v].getValue();
                let key = values[v].getVocabularyId() + '%' + word;

                if (typeof a[key] === 'undefined') {
                    a[key] = {
                        n: 0,
                        f: []
                    }; // n:nbr d'occurences DISTINCTES du mot ; f:flag presence mot dans r
                    options.T_mval.push(values[v]);
                }

                if (!a[key].f[r]) {
                    a[key].n++; // premiere apparition du mot dans le record r
                }
                a[key].f[r] = true; // on ne recomptera pas le mot s'il apparait a nouveau dans le meme record
            }

            n++;
        }

        options.T_mval.sort(_sortCompareMetas);

        var t = '';
        // pour lire le tableau 'a' dans l'ordre trie par 'editor.T_mval'
        for (let i in options.T_mval) {
            let value = options.T_mval[i];
            let word = value.getValue();
            let key = value.getVocabularyId() + '%' + word;

            let extra = value.getVocabularyId()
                ? '<img src="/assets/common/images/icons/ressource16.png" /> '
                : '';

            if (i > 0) {
                if (
                    value.getVocabularyId() !== null &&
                    options.T_mval[i - 1].getVocabularyId() ===
                        value.getVocabularyId()
                ) {
                    continue;
                }
                if (
                    value.getVocabularyId() === null &&
                    options.T_mval[i - 1].getVocabularyId() === null
                ) {
                    if (options.T_mval[i - 1].getValue() === value.getValue()) {
                        continue; // on n'accepte pas les doublons
                    }
                }
            }

            t +=
                '<div  data-index="' +
                i +
                '" class="edit-multivalued-field-action ' +
                (((value.getVocabularyId() === null ||
                    value.getVocabularyId() === vocabularyId) && highlightValue === word )
                    ? ' hilighted '
                    : '') +
                (a[key].n !== n ? ' hetero ' : '') +
                '">' +
                '<table><tr><td>' +
                extra +
                '<span class="value" vocabId="' +
                (value.getVocabularyId() ? value.getVocabularyId() : '') +
                '">' +
                $('<div/>').text(word).html() +
                "</span></td><td class='options'>" +
                '<a href="#" class="add_all"><span class="icon-round-add_box-24px icomoon" style="font-size: 15px"></span></a> ' +
                '<a href="#" class="remove_all"><span class="icon-baseline-indeterminate_check_box-24px icomoon" style="font-size: 15px;"></span></a>' +
                '</td></tr></table>' +
                '</div>';
        }

        $('#ZTextMultiValued_values', options.$container).html(t);

        $('#ZTextMultiValued_values .add_all', options.$container)
            .unbind('click')
            .bind('click', function () {
                let container = $(this).closest('div');

                let span = $('span.value', container);

                let value = span.text();
                let vocab_id = span.attr('vocabid');

                addValueInMultivaluedField({
                    value: value,
                    vocabularyId: vocab_id
                });
                populateFields();
                return false;
            });
        $('#ZTextMultiValued_values .remove_all', options.$container)
            .unbind('click')
            .bind('click', function () {
                let container = $(this).closest('div');

                let span = $('span.value', container);

                let value = span.text();
                let vocab_id = span.attr('vocabid');

                removeValueFromMultivaluedField(value, vocab_id);
                populateFields();
                return false;
            });

        populateFields();
    }

    // ---------------------------------------------------------------------------------------------------------
    // en mode textarea, on clique sur ok, cancel ou fusion
    // appele egalement quand on essaye de changer de champ ou d'image : si ret=false on interdit le changement
    // ---------------------------------------------------------------------------------------------------------
    function validateFieldChanges(evt, action) {
        // action : 'ok', 'fusion' ou 'cancel'
        if (options.fieldCollection.getActiveFieldIndex() === '?') {
            return true;
        }
        let fieldIndex = options.fieldCollection.getActiveFieldIndex();
        let currentField = options.fieldCollection.getActiveField();
        let records = options.recordCollection.getRecords();

        if (action === 'cancel') {
            // on restore le contenu du champ
            if (currentField.type === 'date') {
                $editDateArea.val(options.fieldLastValue);
            } else {
                $editTextArea.val(options.fieldLastValue);
            }

            $editTextArea.trigger('keyup.maxLength');
            options.textareaIsDirty = false;
            return true;
        }

        if (
            action === 'ask_ok' &&
            options.textareaIsDirty &&
            currentField._status === 2
        ) {
            alert(localeService.t('edit_hetero'));
            return false;
        }
        let o = document.getElementById('idEditField_' + fieldIndex);
        if (o !== undefined) {
            let t = $editTextArea.val();

            if (currentField.type === 'date') {
                t = $editDateArea.val();
            }

            for (let recordIndex in records) {
                let record = options.recordCollection.getRecordByIndex(
                    recordIndex
                );
                if (!record._selected) {
                    continue; // on ne modifie pas les fiches non selectionnees
                }

                if (action === 'ok' || action === 'ask_ok') {
                    options.recordCollection.addRecordFieldValue(
                        recordIndex,
                        fieldIndex,
                        {
                            value: t,
                            merge: false,
                            vocabularyId: null
                        }
                    );
                } else if (action === 'fusion' || action === 'ask_fusion') {
                    options.recordCollection.addRecordFieldValue(
                        recordIndex,
                        fieldIndex,
                        {
                            value: t,
                            merge: true,
                            vocabularyId: null
                        }
                    );
                }

                checkRequiredFields(recordIndex, fieldIndex);
            }
        }

        populateFields();

        options.textareaIsDirty = false;

        onSelectField(evt, fieldIndex);
        return true;
    }

    // ---------------------------------------------------------------------------
    // on a clique sur une checkbox de status
    // ---------------------------------------------------------------------------
    function toggleStatus(evt, bit, val) {
        let ck0 = $('#idCheckboxStatbit0_' + bit);
        let ck1 = $('#idCheckboxStatbit1_' + bit);

        switch (val) {
            case 0:
                ck0.attr('class', 'gui_ckbox_1');
                ck1.attr('class', 'gui_ckbox_0');
                break;
            case 1:
                ck0.attr('class', 'gui_ckbox_0');
                ck1.attr('class', 'gui_ckbox_1');
                break;
            default:
        }
        options.recordCollection.setStatus(bit, val);
    }

    // ---------------------------------------------------------------------------
    // on a clique sur une thumbnail
    // ---------------------------------------------------------------------------
    function _onSelectRecord(event, recordIndex) {
        let fieldIndex = options.fieldCollection.getActiveFieldIndex();
        if (fieldIndex >= 0) {
            if (
                options.textareaIsDirty &&
                validateFieldChanges(event, 'ask_ok') === false
            ) {
                return;
            }
        }

        let records = options.recordCollection.getRecords();
        let record = options.recordCollection.getRecordByIndex(recordIndex);

        // guideline : si on mousedown sur une selection, c'est qu'on risque de draguer, donc on ne desectionne pas
        if (event && event.type === 'mousedown' && record._selected) {
            return;
        }

        if (
            event &&
            appCommons.utilsModule.is_shift_key(event) &&
            options.lastClickId !== null
        ) {
            // shift donc on sel du editor.lastClickId a ici
            let pos_from = options.T_pos[options.lastClickId];
            let pos_to = options.T_pos[recordIndex];
            if (pos_from > pos_to) {
                let tmp = pos_from;
                pos_from = pos_to;
                pos_to = tmp;
            }

            for (let pos = pos_from; pos <= pos_to; pos++) {
                let id = options.T_id[pos];
                let record = options.recordCollection.getRecordByIndex(id);
                // toutes les fiches selectionnees
                if (!record._selected) {
                    record._selected = true;
                    $('#idEditDiapo_' + id, options.$container).addClass(
                        'selected'
                    );
                }
            }
        } else {
            if (!event || !appCommons.utilsModule.is_ctrl_key(event)) {
                // on deselectionne tout avant

                for (let recordIndex in records) {
                    let record = options.recordCollection.getRecordByIndex(
                        recordIndex
                    );
                    // toutes les fiches selectionnees
                    if (record._selected) {
                        record._selected = false;
                        $(
                            '#idEditDiapo_' + recordIndex,
                            options.$container
                        ).removeClass('selected');
                    }
                }
            }
            if (recordIndex >= 0) {
                record._selected = !record._selected;
                if (record._selected) {
                    $(
                        '#idEditDiapo_' + recordIndex,
                        options.$container
                    ).addClass('selected');
                } else {
                    $(
                        '#idEditDiapo_' + recordIndex,
                        options.$container
                    ).removeClass('selected');
                }
            }
        }

        let selection = [];
        let allRecords = $('#EDIT_FILM2 .diapo');
        let selected = $('#EDIT_FILM2 .diapo.selected');
        if (selected.length === 1) {
            let r = selected.attr('id').split('_').pop();
            recordEditorEvents.emit('recordEditor.onSelectRecord', {
                recordIndex: r
            });
            selection.push(r);
        } else {
            for (let pos = 0; pos < selected.length; pos++) {
                let $record = $(selected[pos]);
                selection.push($record.attr('id').split('_').pop());
            }
        }

        recordEditorEvents.emit('recordSelection.changed', {
            selection: loadSelectedRecords(),
            selectionPos: getRecordSelection()
        });

        /**trigger select all checkbox**/
        if (selected.length < allRecords.length) {
            $("#select-all-diapo").removeAttr("checked");
        }else{
            if (selected.length == allRecords.length) {
                $("#select-all-diapo").trigger("click");
            }

        };
        options.lastClickId = recordIndex;


        refreshFields(event);
    }

    function getRecordSelection() {
        let selection = [];
        let selected = $('#EDIT_FILM2 .diapo.selected');
        for (let pos = 0; pos < selected.length; pos++) {
            let $record = $(selected[pos]);
            selection.push($record.attr('id').split('_').pop());
        }
        return selection;
    }

    // ----------------------------------------------------------------------------------
    // on a clique sur le 'ok' general : save
    // ----------------------------------------------------------------------------------
    function submitChanges(fnParams) {
        let { event } = fnParams;

        if (
            options.textareaIsDirty &&
            validateFieldChanges(event, 'ask_ok') === false
        ) {
            return false;
        }

        let required_fields = checkRequiredFields();

        if (required_fields) {
            alert(localeService.t('some_required_fields'));
            return false;
        }

        $('#EDIT_ALL', options.$container).hide();
        $('#EDIT_WORKING', options.$container).show();

        let params = {
            mds: options.recordCollection.gatherUpdatedRecords(),
            sbid: options.sbas_id,
            act: 'WORK',
            lst: $('#edit_lst').val(),
            act_option: 'SAVE' + options.what,
            ssel: options.ssel
        };
        if (options.newrepresent !== false) {
            params.newrepresent = options.newrepresent;
        }

        $.ajax({
            url: `${url}prod/records/edit/apply/`,
            data: params,
            type: 'POST',
            success: function (data) {
                if (options.what === 'GRP' || options.what === 'SSEL') {
                    recordEditorEvents.emit('workzone.refresh', {
                        basketId: 'current'
                    });
                }
                closeModal();
                // $('#Edit_copyPreset_dlg').remove();
                // $('#EDITWINDOW').hide();
                // $editorContainer.find('*').addBack().off();
                recordEditorEvents.emit('preview.doReload');
                return;
            }
        });
    }

    function cancelChanges(params) {
        let { event } = params;
        let fieldIndex = options.fieldCollection.getActiveFieldIndex();

        let dirty = false;

        event.cancelBubble = true;
        if (event.stopPropagation) {
            event.stopPropagation();
        }

        if (fieldIndex >= 0) {
            if (
                options.textareaIsDirty &&
                validateFieldChanges(event, 'ask_ok') === false
            ) {
                return;
            }
        }

        dirty = options.recordCollection.isDirty();
        if (!dirty || confirm(localeService.t('confirm_abandon'))) {
            closeModal();
        }
    }

    const closeModal = () => {
        $('#Edit_copyPreset_dlg').remove();
        $('#idFrameE .ww_content', options.$container).empty();

        $toolsTabs.hide().tabs('destroy');
        $container.find('*').addBack().off();
        $container.fadeOut().empty();
        options = {};
        recordEditorEvents.dispose();
    };

    function setActiveField(fieldIndex) {
        // let metaStructId = parseInt(options.curField, 10);
        options.fieldCollection.setActiveField(fieldIndex);
        fieldIndex =
            isNaN(fieldIndex) || fieldIndex < 0 ? 'status' : fieldIndex;

        $('#divS div.active, #divS div.hover').removeClass('active hover');
        $('#EditFieldBox_' + fieldIndex).addClass('active');

        let cont = $('#divS');
        let calc =
            $('#EditFieldBox_' + fieldIndex).offset().top - cont.offset().top; // hauteur relative par rapport au visible

        if (calc > cont.height() || calc < 0) {
            cont.scrollTop(calc + cont.scrollTop());
        }
    }

    function _sortCompareMetas(a, b) {
        if (typeof a !== 'object') {
            return -1;
        }
        if (typeof b !== 'object') {
            return 1;
        }
        let na = a.getValue().toUpperCase();
        let nb = b.getValue().toUpperCase();
        if (na === nb) {
            return 0;
        }
        return na < nb ? -1 : 1;
    }

    function checkRequiredFields(inputRecordIndex, inputFieldIndex) {
        let fieldCollection = options.fieldCollection.getFields();
        let recordCollection = options.recordCollection.getRecords();
        let requiredFields = false;

        if (typeof inputRecordIndex === 'undefined') {
            inputRecordIndex = false;
        }
        if (typeof inputFieldIndex === 'undefined') {
            inputFieldIndex = false;
        }

        for (let fieldIndex in fieldCollection) {
            let currentField = options.fieldCollection.getFieldByIndex(
                fieldIndex
            );
            if (inputFieldIndex !== false && fieldIndex !== inputFieldIndex) {
                continue;
            }

            if (!currentField.required) {
                continue;
            }

            for (let recordIndex in recordCollection) {
                let currentRecord = options.recordCollection.getRecordByIndex(
                    recordIndex
                );
                if (
                    inputRecordIndex !== false &&
                    recordIndex !== inputRecordIndex
                ) {
                    continue;
                }

                let elem = $('#idEditDiapo_' + recordIndex + ' .require_alert');

                elem.hide();

                if (!currentRecord.fields[fieldIndex]) {
                    elem.show();
                    requiredFields = true;
                } else {
                    let checkRequired = '';

                    // le champ existe dans la fiche
                    if (currentField.multi) {
                        // champ multi : on compare la concat des valeurs
                        checkRequired = $.trim(
                            currentRecord.fields[
                                fieldIndex
                            ].getSerializedValues()
                        );
                    } else if (currentRecord.fields[fieldIndex].getValue()) {
                        checkRequired = $.trim(
                            currentRecord.fields[fieldIndex]
                                .getValue()
                                .getValue()
                        );
                    }

                    if (checkRequired === '') {
                        elem.show();
                        requiredFields = true;
                    }
                }
            }
        }
        return requiredFields;
    }

    function _edit_select_all() {
        let records = options.recordCollection.getRecords();
        $('#EDIT_FILM2 .diapo', options.$container).addClass('selected');

        for (let i in records) {
            records[i]._selected = true;
        }

        options.lastClickId = 1;

        refreshFields(null); // null : no evt available
    }

    function _edit_select_all_right(check) {
        let records = options.recordCollection.getRecords();
        console.log(records);
        if (check == true) {
            $('#EDIT_FILM2 .diapo', options.$container).addClass('selected');
        } else {
            $('#EDIT_FILM2 .diapo', options.$container).removeClass('selected');
        }
        for (let i in records) {
            if (records[i].type !== "unknown") {
                records[i]._selected = check;
            }
        }
        options.lastClickId = 0;

        refreshFields(null); // null : no evt available
    }


    // ---------------------------------------------------------------------------
    // highlight la valeur en cours de saisie dans la liste des multi-valeurs
    // appele par le onkeyup
    // ---------------------------------------------------------------------------
    function _reveal_mval(value, vocabularyId) {
        let records = options.recordCollection.getRecords();
        let fieldIndex = options.fieldCollection.getActiveFieldIndex();
        let currentField = options.fieldCollection.getActiveField();
        let talt;

        if (typeof vocabularyId === 'undefined') {
            vocabularyId = null;
        }

        /*if (currentField.tbranch) {
         if (value !== '') {
         appEvents.emit('recordEditor.userInputValue', {
         context: {
         event: false,
         currentField,
         },
         value
         });
         // ETHSeeker.search(value);
         }
         }*/
        onUserInputComplete(false, value, currentField);

        if (value !== '') {
            // 		let nsel = 0;
            for (let recordIndex in records) {
                let currentRecord = options.recordCollection.getRecordByIndex(
                    recordIndex
                );
                if (
                    currentRecord.fields[fieldIndex].hasValue(
                        value,
                        vocabularyId
                    )
                ) {
                    $('#idEditDiaButtonsP_' + recordIndex).hide();
                    talt = sprintf(localeService.t('editDelSimple'), value);
                    $('#idEditDiaButtonsM_' + recordIndex)
                        .show()
                        .attr('alt', talt)
                        .attr('Title', talt)
                        .unbind('click')
                        .bind('click', function () {
                            let indice = $(this).attr('id').split('_').pop();
                            _edit_diabutton(indice, 'del', value, vocabularyId);
                        });
                } else {
                    $('#idEditDiaButtonsM_' + recordIndex).hide();
                    $('#idEditDiaButtonsP_' + recordIndex).show();
                    talt = sprintf(localeService.t('editAddSimple'), value);
                    $('#idEditDiaButtonsP_' + recordIndex)
                        .show()
                        .attr('alt', talt)
                        .attr('Title', talt)
                        .unbind('click')
                        .bind('click', function () {
                            let indice = $(this).attr('id').split('_').pop();
                            _edit_diabutton(indice, 'add', value, vocabularyId);
                        });
                }
            }
            $('.editDiaButtons', options.$container).show();
        }

        $editMultiValTextArea.trigger('focus');
        return true;
    }

    /**
     * Remove a value from a multivalued field
     * @param value
     * @param vocabularyId
     */
    function removeValueFromMultivaluedField(value, vocabularyId) {
        let records = options.recordCollection.getRecords();
        let fieldIndex = options.fieldCollection.getActiveFieldIndex();

        for (let recordIndex in records) {
            let currentRecord = options.recordCollection.getRecordByIndex(
                recordIndex
            );

            if (!currentRecord._selected) {
                continue;
            }
            options.recordCollection.removeRecordFieldValue(
                recordIndex,
                fieldIndex,
                {
                    value,
                    vocabularyId
                }
            );
        }
        refreshFields(null);
    }

    /**
     * Add a value into a multivalued field
     * @param params
     */
    function addValueInMultivaluedField(params) {
        let { value, vocabularyId } = params;
        let records = options.recordCollection.getRecords();
        let fieldIndex = options.fieldCollection.getActiveFieldIndex();

        vocabularyId = vocabularyId === undefined ? null : vocabularyId;

        for (let recordIndex in records) {
            let currentRecord = options.recordCollection.getRecordByIndex(
                recordIndex
            );

            if (!currentRecord._selected) {
                continue;
            }

            options.recordCollection.addRecordFieldValue(
                recordIndex,
                fieldIndex,
                {
                    value,
                    merge: false,
                    vocabularyId
                }
            );
        }
        refreshFields(null);
    }

    // ---------------------------------------------------------------------------
    // on a clique sur une des multi-valeurs dans la liste
    // ---------------------------------------------------------------------------
    function _editMultivaluedField(mvaldiv, ival) {
        $(mvaldiv).parent().find('.hilighted').removeClass('hilighted');
        $(mvaldiv).addClass('hilighted');
        _reveal_mval(
            options.T_mval[ival].getValue(),
            options.T_mval[ival].getVocabularyId()
        );
    }

    function _edit_diabutton(recordIndex, act, value, vocabularyId) {
        let fieldIndex = options.fieldCollection.getActiveFieldIndex();
        if (act === 'del') {
            options.recordCollection.removeRecordFieldValue(
                recordIndex,
                fieldIndex,
                {
                    value,
                    vocabularyId
                }
            );
        }

        if (act === 'add') {
            options.recordCollection.addRecordFieldValue(
                recordIndex,
                fieldIndex,
                {
                    value,
                    merge: false,
                    vocabularyId
                }
            );
        }
        _updateCurrentMval(fieldIndex, value, vocabularyId);
        _reveal_mval(value, vocabularyId);
    }

    // ---------------------------------------------------------------------------
    // change de champ (avec les fleches autour du nom champ)
    // ---------------------------------------------------------------------------
    // edit_chgFld
    function fieldNavigate(evt, dir) {
        let current_field = $('#divS .edit_field.active');
        if (current_field.length === 0) {
            current_field = $('#divS .edit_field:first');
            current_field.trigger('click');
        } else {
            if (dir >= 0) {
                current_field.next().trigger('click');
            } else {
                current_field.prev().trigger('click');
            }
        }
    }

    const _onTextareaKeyDown = event => {
        let currentField = options.fieldCollection.getActiveField();
        let $el = $(event.currentTarget);
        let cancelKey = false;

        switch (event.keyCode) {
            case 13:
            case 10:
                if (currentField.type === 'date') {
                    cancelKey = true;
                }
                break;
            default:
        }

        if (cancelKey) {
            event.cancelBubble = true;
            if (event.stopPropagation) {
                event.stopPropagation();
            }
            return false;
        }
        return true;
    };

    // ----------------------------------------------------------------------------------------------
    // des events sur le textarea pour tracker la selection (chercher dans le thesaurus...)
    // ----------------------------------------------------------------------------------------------
    const _onTextareaMouseDown = evt => {
        evt.cancelBubble = true;
        return true;
    };

    // mouse up textarea
    const _onTextareaMouseUp = (event, obj) => {
        let currentField = options.fieldCollection.getActiveField();
        let $el = $(event.currentTarget);
        let value = $el.val();

        onUserInputComplete(event, value, currentField);
        return true;
    };

    // key up textarea
    const _onTextareaKeyUp = (event, obj) => {
        let currentField = options.fieldCollection.getActiveField();
        let $el = $(event.currentTarget);
        let cancelKey = false;
        let o;
        switch (event.keyCode) {
            case 27: // esc : on restore la valeur avant editing
                // 			$("#btn_cancel", editor.$container).parent().css("backgroundColor", "#000000");
                validateFieldChanges(event, 'cancel');
                // 			self.setTimeout("document.getElementById('btn_cancel').parentNode.style.backgroundColor = '';", 100);
                cancelKey = true;
                break;
            default:
        }

        if (cancelKey) {
            event.cancelBubble = true;
            if (event.stopPropagation) {
                event.stopPropagation();
            }
            return false;
        }
        if (
            !options.textareaIsDirty &&
            $editTextArea.val() !== options.fieldLastValue
        ) {
            options.textareaIsDirty = true;
        }

        let searchValue = $el.val(); // obj.value;

        onUserInputComplete(event, searchValue, currentField);
        return true;
    };

    /**
     * debounceable method
     * @param event
     * @param value
     * @param field
     */
    let onUserInputComplete = (event, value, field) => {
        if (value !== '') {
            recordEditorEvents.emit('recordEditor.userInputValue', {
                event,
                value,
                field
            });
        }
    };

    /**
     * add field value from a datasource
     * if the field is not specified, use active one
     * @param params
     */
    const addValueFromDataSource = params => {
        let { value, field } = params;

        if (field === undefined || field === null) {
            field = options.fieldCollection.getActiveField();
        }

        if (field.multi) {
            $editMultiValTextArea.val(value);
            $editMultiValTextArea.trigger('keyup.maxLength');
            recordEditorEvents.emit('recordEditor.addMultivaluedField', {
                value: $editMultiValTextArea.val()
            });
        } else {
            $editTextArea.val(value);
            $editTextArea.trigger('keyup.maxLength');
            options.textareaIsDirty = true;
        }
    };

    /**
     * Bulk field update
     * @param params
     */
    const addPresetValuesFromDataSource = params => {
        let { data } = params;
        let mode = params.mode || '';
        let preselectedRecord = params.recordIndex || false;

        let records = options.recordCollection.getRecords();
        let fields = options.fieldCollection.getFields();

        for (let fieldIndex in fields) {
            let field = options.fieldCollection.getFieldByIndex(fieldIndex);
            field.preset = null;
            if (typeof data.fields[field.name] !== 'undefined') {
                field.preset = data.fields[field.name];
            }
            options.fieldCollection.updateField(fieldIndex, field);
        }

        // apply new preset value on each record's fields
        for (let recordIndex in records) {
            let record = options.recordCollection.getRecordByIndex(recordIndex);
            if (!record._selected) {
                continue;
            }

            for (let fieldIndex in fields) {
                let field = options.fieldCollection.getFieldByIndex(fieldIndex);
                if (field.preset !== null) {
                    for (let val in field.preset) {
                        if (preselectedRecord !== false) {
                            if (preselectedRecord !== recordIndex) {
                                // only update preselected record
                                continue;
                            }
                        }
                        // don't update filled fields in emptyOnly mode:
                        if (
                            mode === 'emptyOnly' &&
                            field._value !== '' &&
                            !record.fields[fieldIndex].isDirty()
                        ) {
                            continue;
                        }
                        options.recordCollection.addRecordFieldValue(
                            recordIndex,
                            fieldIndex,
                            {
                                value: field.preset[val].trim(),
                                merge: false,
                                vocabularyId: null
                            }
                        );
                    }
                }
            }
        }
        recordEditorEvents.emit('recordEditor.onUpdateFields');
    };

    /**
     * get selected records field values
     * @returns {Array}
     */
    const loadSelectedRecords = () => {
        let records = options.recordCollection.getRecords();
        let fields = options.fieldCollection.getFields();
        let selectedRecords = [];
        for (let recordIndex in records) {
            let recordFieldValue = {};
            let record = options.recordCollection.getRecordByIndex(recordIndex);
            if (!record._selected) {
                continue;
            }
            recordFieldValue["_rid"] = record.rid;
            for (var _recordIndex in options.recordConfig.records) {
                if (options.recordConfig.records[_recordIndex].id === record.rid) {
                    recordFieldValue["technicalInfo"] = options.recordConfig.records[_recordIndex].technicalInfo;
                }
            }
            for (let fieldIndex in fields) {
                let field = options.fieldCollection.getFieldByIndex(fieldIndex);
                let value = null;

                // retrieve original record value (of field)
                if (field.multi) {
                    value = record.fields[fieldIndex].getSerializedValues();
                } else {
                    let fieldData = record.fields[fieldIndex].getValue();
                    if (fieldData !== null) {
                        if (fieldData.datas !== undefined) {
                            value = fieldData.datas.value;
                        }
                    }
                }
                recordFieldValue[field.name] = value;
            }
            selectedRecords.push(recordFieldValue);
        }
        return selectedRecords;
    };

    const appendTab = params => {
        let { tabProperties, position } = params;
        const $appendAfterTab = $(
            `.tabs ul li:eq(${position - 1})`,
            $container
        );

        const newTab = `<li><a href="#${tabProperties.id}">${tabProperties.title}</a></li>`;
        $appendAfterTab.after(newTab);

        const appendAfterTabContent = $(
            `.tabs > div:eq(${position - 1})`,
            $container
        );
        appendAfterTabContent.after(`<div id="${tabProperties.id}"></div>`);

        try {
            $toolsTabs.tabs('refresh');
        } catch (e) {}

        recordEditorEvents.emit('appendTab.complete', {
            origParams: params,
            selection: loadSelectedRecords()
        });
    };
    const activateToolTab = tabId => {
        $toolsTabs.tabs(
            'option',
            'active',
            $toolsTabs.find(`#${tabId}`).index() - 1
        );
    };

    return {
        initialize
        //onGlobalKeydown: onGlobalKeydown,
    };
};
export default recordEditorService;
