import $ from 'jquery';
import {cleanTags} from '../../utils/utils';

const presetsModule = (services) => {
    const {configService, localeService, recordEditorEvents} = services;
    const url = configService.get('baseUrl');
    let $container = null;
    let parentOptions = {};
    let recordCollection;
    let fieldCollection;
    const initialize = (options) => {
        let initWith = {$container, parentOptions} = options;
        recordCollection = parentOptions.recordCollection;
        fieldCollection = parentOptions.fieldCollection;
        initPresetsModal();
    };

    const initPresetsModal = () => {
        let buttons = {};
        buttons[localeService.t('valider')] = function (event) {
            $(this).dialog('close');
            recordEditorEvents.emit('recordEditor.submitAllChanges', {event});
        };
        buttons[localeService.t('annuler')] = function (event) {
            $(this).dialog('close');
            recordEditorEvents.emit('recordEditor.cancelAllChanges', {event});
        };

        $('#EDIT_CLOSEDIALOG', $container).dialog({
            autoOpen: false,
            closeOnEscape: true,
            resizable: false,
            draggable: false,
            modal: true,
            buttons: buttons
        });

        buttons[localeService.t('valider')] = function () {
            var form = $('#Edit_copyPreset_dlg FORM');
            var jtitle = $('.EDIT_presetTitle', form);
            if (jtitle.val() === '') {
                alert(localeService.t('needTitle'));
                jtitle[0].focus();
                return;
            }
            var addFields = [];
            $(':checkbox', form).each(function (idx, elem) {
                var $el = $(elem);
                if ($el.is(':checked')) {
                    var fieldIndex = $el.val();
                    let foundField = fieldCollection.getFieldByIndex(fieldIndex);
                    var field = {
                        name: foundField.name,
                        value: []
                    };
                    var tval;
                    if (foundField.multi) {
                        field.value = $.map(
                            foundField._value.split(';'),
                            function (obj, idx) {
                                return obj.trim();
                            }
                        );
                    } else {
                        field.value = [foundField._value.trim()];
                    }
                    addFields.push(field);
                }
            });

            $.ajax({
                type: 'POST',
                url: `${url}prod/records/edit/presets`,
                data: {
                    sbas_id: parentOptions.sbas_id,
                    title: jtitle.val(),
                    fields: addFields
                },
                dataType: 'json',
                success: function (data, textStatus) {
                    _preset_paint(data);

                    if ($('#Edit_copyPreset_dlg').data('ui-dialog')) {
                        $('#Edit_copyPreset_dlg').dialog('close');
                    }
                }
            });
        };

        buttons[localeService.t('annuler')] = function () {
            $(this).dialog('close');

        };

        $('#Edit_copyPreset_dlg', $container).dialog({
            stack: true,
            closeOnEscape: true,
            resizable: false,
            draggable: false,
            autoOpen: false,
            modal: true,
            width: 600,
            title: localeService.t('newPreset'),
            close: function (event, ui) {
                $(this).dialog('widget').css('z-index', 'auto');
            },
            open: function (event, ui) {
                $(this).dialog('widget').css('z-index', '5000');
                $('.EDIT_presetTitle')[0].focus();
            },
            buttons: buttons
        });

        $.ajax({
            type: 'GET',
            url: `${url}prod/records/edit/presets`,
            data: {
                sbas_id: parentOptions.sbas_id
            },
            dataType: 'json',
            success: function (data, textStatus) {
                _preset_paint(data);
            }
        });
        $container.on('click', '#TH_Opresets button.adder', function () {
            //$('#TH_Opresets button.adder').bind('click', function () {
            _preset_copy();
        });
    }

    function _preset_paint(data) {
        $('.EDIT_presets_list', parentOptions.$container).html(data.html);

        $container.on('click', '.EDIT_presets_list A.triangle', function () {
                $(this).parent().parent().toggleClass('opened');
                return false;
            }
        );
        $container.on('dblclick', '.EDIT_presets_list A.title', function () {
                var preset_id = $(this).parent().parent().attr('id');
                if (preset_id.substr(0, 12) === 'EDIT_PRESET_') {
                    _preset_load(preset_id.substr(12));
                }
                return false;
            }
        );
        $container.on('click', '.EDIT_presets_list A.delete', function () {
                var li = $(this).closest('LI');
                var preset_id = li.attr('id');
                var title = $(this).parent().children('.title').html();
                if (preset_id.substr(0, 12) === 'EDIT_PRESET_' && confirm("supprimer le preset '" + title + "' ?")) {
                    _preset_delete(preset_id.substr(12), li);
                }
                return false;
            }
        );
    }

    function _preset_copy() {
        var html = '';
        let fields = fieldCollection.getFields();

        for (let fieldIndex in fields) {
            let field = fieldCollection.getFieldByIndex(fieldIndex);
            if (field._status === 1) {
                if (field.readonly) {
                    continue;
                }
                var c = field._value === '' ? '' : 'checked="1"';
                html += '<div><label class="checkbox" for="new_preset_' + field.name + '"><input type="checkbox" class="checkbox" id="new_preset_' + field.name + '" value="' + fieldIndex + '" ' + c + '/>' + '<b>' + field.label + ' : </b></label> ';
                html += cleanTags(field._value) + '</div>';
            }
        }
        $('#Edit_copyPreset_dlg FORM DIV').html(html);
        var $dialog = $('#Edit_copyPreset_dlg');
        if ($dialog.data('ui-dialog')) {
            // to show dialog on top of edit window
            $dialog.dialog('widget').css('z-index', 1300);
            $dialog.dialog('open');
        }
    }

    function _preset_delete(presetId, li) {
        $.ajax({
            type: 'DELETE',
            url: `${url}prod/records/edit/presets/${presetId}`,
            data: {},
            dataType: 'json',
            success: function (data, textStatus) {
                li.remove();
            }
        });
    }

    function _preset_load(presetId) {
        $.ajax({
            type: 'GET',
            url: `${url}prod/records/edit/presets/${presetId}`,
            data: {},
            dataType: 'json',
            success: function (data, textStatus) {
                if ($('#Edit_copyPreset_dlg').data('ui-dialog')) {
                    $('#Edit_copyPreset_dlg').dialog('close');
                }
                let records = recordCollection.getRecords();
                let fields = fieldCollection.getFields();

                for (let fieldIndex in fields) {
                    let field = fieldCollection.getFieldByIndex(fieldIndex);
                    field.preset = null;
                    if (typeof (data.fields[field.name]) !== 'undefined') {
                        field.preset = data.fields[field.name];
                    }
                    fieldCollection.updateField(fieldIndex, field);
                }
                for (let recordIndex in records) {
                    let record = recordCollection.getRecordByIndex(recordIndex);
                    if (!record._selected) {
                        continue;
                    }

                    for (let fieldIndex in fields) {
                        let field = fieldCollection.getFieldByIndex(fieldIndex);
                        if (field.preset !== null) {
                            for (let val in field.preset) {
                                // fix : some (old, malformed) presets values may need trim()
                                recordCollection.addRecordFieldValue(recordIndex, fieldIndex, {
                                    value: field.preset[val].trim(), merge: false, vocabularyId: null
                                });
                            }
                        }
                    }
                }
                recordEditorEvents.emit('recordEditor.onUpdateFields');
            }
        });
    }

    return {initialize};
};
export default presetsModule;
