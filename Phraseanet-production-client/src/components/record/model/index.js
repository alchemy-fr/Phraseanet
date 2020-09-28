import $ from 'jquery';

function checkVocabId(VocabularyId) {
    if (typeof VocabularyId === 'undefined') {
        VocabularyId = null;
    }

    if (VocabularyId === '') {
        VocabularyId = null;
    }

    return VocabularyId;
}

var recordFieldValue = function (meta_id, value, VocabularyId) {

    VocabularyId = checkVocabId(VocabularyId);

    this.datas = {
        meta_id: meta_id,
        value: value,
        VocabularyId: VocabularyId
    };

    var $this = this;
};

recordFieldValue.prototype = {
    getValue: function () {
        return this.datas.value;
    },
    getMetaId: function () {
        return this.datas.meta_id;
    },
    getVocabularyId: function () {
        return this.datas.VocabularyId;
    },
    setValue: function (value, VocabularyId) {

        this.datas.value = value;
        this.datas.VocabularyId = checkVocabId(VocabularyId);
        return this;
    },
    remove: function () {
        this.datas.value = '';
        this.datas.VocabularyId = null;

        return this;
    }
};

var databoxField = function (name, label, meta_struct_id, options) {

    var defaults = {
            multi: false,
            required: false,
            readonly: false,
            maxLength: null,
            minLength: null,
            type: 'string',
            separator: null,
            vocabularyControl: null,
            vocabularyRestricted: false
        };

    options = (typeof options === 'object') ? options : {};

    if (isNaN(meta_struct_id)) {
        throw 'meta_struct_id should be a number';
    }

    this.name = name;
    this.label = label;
    this.meta_struct_id = meta_struct_id;
    this.options = $.extend(defaults, options);

    if (this.options.multi === true && this.options.separator === null) {
        this.options.separator = ';';
    }

};

databoxField.prototype = {
    getMetaStructId: function () {
        return this.meta_struct_id;
    },
    getName: function () {
        return this.name;
    },
    getLabel: function () {
        return this.label;
    },
    isMulti: function () {
        return this.options.multi;
    },
    isRequired: function () {
        return this.options.required;
    },
    isReadonly: function () {
        return this.options.readonly;
    },
    getMaxLength: function () {
        return this.options.maxLength;
    },
    getMinLength: function () {
        return this.options.minLength;
    },
    getType: function () {
        return this.options.type;
    },
    getSeparator: function () {
        return this.options.separator;
    }
};

var recordField = function (databoxField, arrayValues) {

    this.databoxField = databoxField;
    this.options = {
        dirty: false
    };
    this.datas = [];

    if (arrayValues instanceof Array) {
        if (arrayValues.length > 1 && !databoxField.isMulti()) {
            throw 'You can not add multiple values to a non multi field ' + databoxField.getName();
        }

        var first = true;

        for (let v in arrayValues) {
            if (typeof arrayValues[v] !== 'object') {
                if (window.console) {
                    console.error('Trying to add a non-recordFieldValue to the field...');
                }

                continue;
            }

            if (isNaN(arrayValues[v].getMetaId())) {
                if (window.console) {
                    console.error('Trying to add a recordFieldValue without metaId...');
                }

                continue;
            }

            if (!first && this.options.multi === false) {
                if (window.console) {
                    console.error('Trying to add multi values in a non-multi field');
                }
            }

            /*if (window.console) {
                console.log('adding a value : ', arrayValues[v]);
            }*/

            this.datas.push(arrayValues[v]);
            first = false;
        }
    }

    var $this = this;
};
recordField.prototype = {
    getName: function () {
        return this.databoxField.getName();
    },
    getMetaStructId: function () {
        return this.databoxField.getMetaStructId();
    },
    isMulti: function () {
        return this.databoxField.isMulti();
    },
    isRequired: function () {
        return this.databoxField.isRequired();
    },
    isDirty: function () {
        return this.options.dirty;
    },
    addValue: function (value, merge, VocabularyId) {

        VocabularyId = checkVocabId(VocabularyId);

        merge = !!merge;

/*        if (this.databoxField.isReadonly()) {
            if (window.console) {
                console.error('Unable to set a value to a readonly field');
            }

            return this;
        }*/

        if (window.console) {
            console.log('adding value ', value, ' vocId : ', VocabularyId, '  ; merge is ', merge);
        }

        if (this.isMulti()) {
            if (!this.hasValue(value, VocabularyId)) {
                if (window.console) {
                    console.log('adding new multi value ', value);
                }
                this.datas.push(new recordFieldValue(null, value, VocabularyId));
                this.options.dirty = true;
            } else {
                if (window.console) {
                    console.log('already have ', value);
                }
            }
        } else {
            if (merge === true && this.isEmpty() === false && VocabularyId === null) {
                if (window.console) {
                    console.log('Merging value ', value);
                }
                this.datas[0].setValue(this.datas[0].getValue() + ' ' + value, VocabularyId);

                this.options.dirty = true;
            } else {
                if (merge === true && this.isEmpty() === false && VocabularyId !== null) {
                    if (window.console) {
                        console.error('Cannot merge vocabularies');
                    }
                    this.datas[0].setValue(value, VocabularyId);
                } else {

                    if (!this.hasValue(value, VocabularyId)) {
                        if (this.datas.length === 0) {
                            /*if (window.console) {
                                console.log('Adding new value ', value);
                            }*/
                            this.datas.push(new recordFieldValue(null, value, VocabularyId));
                        } else {
                            if (window.console) {
                                console.log('Updating value ', value);
                            }
                            this.datas[0].setValue(value, VocabularyId);
                        }
                        this.options.dirty = true;
                    }
                }
            }
        }

        return this;
    },
    hasValue: function (value, VocabularyId) {

        if (typeof value === 'undefined') {
            if (window.console) {
                console.error('Trying to check the presence of an undefined value');
            }
        }

        VocabularyId = checkVocabId(VocabularyId);

        for (let d in this.datas) {
            if (VocabularyId !== null) {
                if (this.datas[d].getVocabularyId() === VocabularyId) {
                    if (window.console) {
                        console.log('already got the vocab ID');
                    }
                    return true;
                }
            } else if (this.datas[d].getVocabularyId() === null && this.datas[d].getValue() === value) {
                if (window.console) {
                    console.log('already got this value');
                }
                return true;
            }
        }
        return false;
    },
    removeValue: function (value, vocabularyId) {

        if (this.databoxField.isReadonly()) {
            if (window.console) {
                console.error('Unable to set a value to a readonly field');
            }

            return this;
        }

        vocabularyId = checkVocabId(vocabularyId);

        if (window.console) {
            console.log('Try to remove value ', value, vocabularyId, this.datas);
        }

        for (let d in this.datas) {
            if (window.console) {
                console.log('loopin... ', this.datas[d].getValue());
            }
            if (this.datas[d].getVocabularyId() !== null) {
                if (this.datas[d].getVocabularyId() === vocabularyId) {
                    if (window.console) {
                        console.log('Found within the vocab ! removing... ');
                    }
                    this.datas[d].remove();
                    this.options.dirty = true;
                }
            } else if (this.datas[d].getValue() === value) {
                if (window.console) {
                    console.log('Found ! removing... ');
                }
                this.datas[d].remove();
                this.options.dirty = true;
            }
        }
        return this;
    },
    isEmpty: function () {
        var empty = true;

        for (let d in this.datas) {
            if (this.datas[d].getValue() !== '') {
                empty = false;
            }
        }
        return empty;
    },
    empty: function () {

        if (this.databoxField.isReadonly()) {
            if (window.console) {
                console.error('Unable to set a value to a readonly field');
            }

            return this;
        }

        for (let d in this.datas) {
            this.datas[d].remove();
            this.options.dirty = true;
        }
        return this;
    },
    getValue: function () {

        if (this.isMulti()) {
            throw 'This field is multi, I can not give you a single value';
        }

        if (this.isEmpty()) {
            return null;
        }

        return this.datas[0];
    },
    getValues: function () {

        if (!this.isMulti()) {
            throw 'This field is not multi, I can not give you multiple values';
        }

        if (this.isEmpty()) {
            return [];
        }

        var arrayValues = [];

        for (let d in this.datas) {
            if (this.datas[d].getValue() === '') {
                continue;
            }

            arrayValues.push(this.datas[d]);
        }

        return arrayValues;
    },
    sort: function (algo) {
        this.datas.sort(algo);

        return this;
    },
    getSerializedValues: function () {

        var arrayValues = [];
        var values = this.getValues();

        for (let v in values) {
            arrayValues.push(values[v].getValue());
        }

        return arrayValues.join(' ; ');
    },
    replaceValue: function (search, replace) {

        if (this.databoxField.isReadonly()) {
            if (window.console) {
                console.error('Unable to set a value to a readonly field');
            }

            return 0;
        }

        var n = 0;

        for (let d in this.datas) {
            if (this.datas[d].getVocabularyId() !== null) {
                continue;
            }

            var value = this.datas[d].getValue();
            var replacedValue = value.replace(search, replace);

            if (value === replacedValue) {
                continue;
            }

            n++;

            this.removeValue(value);

            if (!this.hasValue(replacedValue)) {
                this.addValue(replacedValue);
            }

            this.options.dirty = true;
        }

        return n;
    },
    exportDatas: function () {

        var returnValue = [];

        for (let d in this.datas) {
            var temp = {
                meta_id: this.datas[d].getMetaId() ? this.datas[d].getMetaId() : '',
                meta_struct_id: this.getMetaStructId(),
                value: this.datas[d].getValue()
            };

            if (this.datas[d].getVocabularyId()) {
                temp.vocabularyId = this.datas[d].getVocabularyId();
            }
            returnValue.push(temp);
        }

        return returnValue;
    }
};

export {
    databoxField, recordFieldValue, recordField
};

