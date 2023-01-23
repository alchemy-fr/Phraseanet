import FieldCollection from './fieldCollection';
import * as recordModel from '../../../record/model';
class RecordCollection {
    records;
    fieldCollection;
    statbitCollection;
    constructor(recordsList, fieldsColl, statusbitList, predefinedValues) {
        this.records = recordsList;
        this.statusbits = statusbitList;
        this.fieldCollection = fieldsColl;
        this.predefinedValues = predefinedValues;
        // set each models
        this.initializeRecordModels();
    }
    getRecords() {
        return this.records;
    }
    getRecordByIndex(id = false) {
        if (this.records[id] !== undefined) {
            return this.records[id];
        }
        return false;
    }

    initializeRecordModels() {
        for (let recordIndex in this.records) {
            // let fields = this.setRecordFields(recordIndex);
            this.records[recordIndex].fields = this.setRecordFields(recordIndex);
            //options.fields = fields;

        }
    }
    setRecordFields(recordIndex) {
        let fields = {};
        for (let fieldIndex in this.records[recordIndex].fields) {
            var meta_struct_id = this.records[recordIndex].fields[fieldIndex].meta_struct_id;


            let currentField = this.fieldCollection.getFieldByIndex(meta_struct_id);
            var fieldOptions = this.fieldCollection.getFieldOptionsById(meta_struct_id);

            var databoxField = new recordModel.databoxField(currentField.name, currentField.label, meta_struct_id, fieldOptions);

            var values = [];

            for (let v in this.records[recordIndex].fields[fieldIndex].values) {
                var meta_id = this.records[recordIndex].fields[fieldIndex].values[v].meta_id;
                var value = this.records[recordIndex].fields[fieldIndex].values[v].value;
                var vocabularyId = this.records[recordIndex].fields[fieldIndex].values[v].vocabularyId;

                values.push(new recordModel.recordFieldValue(meta_id, value, vocabularyId));
            }

            fields[fieldIndex] = new recordModel.recordField(databoxField, values);
        }
        return fields;
    }

    /**
     * Merge all suggestions for 1 or n records
     * @returns {{}}
     */
    getFieldSuggestedValues() {
        var suggestedValuesCollection = {};
        var t_selcol = {};
        var ncolsel = 0;
        var nrecsel = 0;
        for (let recordIndex in this.records) {
            if (!this.records[recordIndex]._selected) {
                continue;
            }
            nrecsel++;

            var bid = 'b' + this.records[recordIndex].bid;
            if (t_selcol[bid]) {
                continue;
            }

            t_selcol[bid] = 1;
            ncolsel++;
            for (let f in this.predefinedValues[bid]) {
                if (!suggestedValuesCollection[f]) {
                    suggestedValuesCollection[f] = {};
                }
                for (let ivs in this.predefinedValues[bid][f]) {
                    let vs = this.predefinedValues[bid][f][ivs];
                    if (!suggestedValuesCollection[f][vs]) {
                        suggestedValuesCollection[f][vs] = 0;
                    }
                    suggestedValuesCollection[f][vs]++;
                }
            }
        }

        return suggestedValuesCollection;
    }

    setStatus(bit, val) {
        for (let id in this.records) {
            // toutes les fiches selectionnees
            if (this.records[id]._selected) {
                if (this.records[id].editableStatus === true) {
                    this.records[id].statbits[bit].value = val;
                    this.records[id].statbits[bit].dirty = true;
                }
            }
        }
    }

    isDirty() {
        let dirty = false;
        for (let recordIndex in this.records) {
            for (let fieldIndex in this.records[recordIndex].fields) {
                if ((dirty |= this.records[recordIndex].fields[fieldIndex].isDirty())) {
                    break;
                }
            }
            for (let bitIndex in this.records[recordIndex].statbits) {
                if ((dirty |= this.records[recordIndex].statbits[bitIndex].dirty)) {
                    break;
                }
            }
        }
        return dirty;
    }

    gatherUpdatedRecords() {
        let t = [];

        for (let recordIndex in this.records) {
            let record_datas = {
                record_id: this.records[recordIndex].rid,
                metadatas: [],
                edit: 0,
                status: null
            };

            let editDirty = false;

            for (let f in this.records[recordIndex].fields) {
                if (!this.records[recordIndex].fields[f].isDirty()) {
                    continue;
                }

                editDirty = true;
                record_datas.edit = 1;

                record_datas.metadatas = record_datas.metadatas.concat(
                    this.records[recordIndex].fields[f].exportDatas()
                );
            }

            // les statbits
            let tsb = [];
            for (let n = 0; n < 64; n++) {
                tsb[n] = 'x';
            }
            let sb_dirty = false;
            for (let n in this.records[recordIndex].statbits) {
                if (this.records[recordIndex].statbits[n].dirty) {
                    tsb[63 - n] = this.records[recordIndex].statbits[n].value;
                    sb_dirty = true;
                }
            }

            if (sb_dirty || editDirty) {
                if (sb_dirty === true) {
                    record_datas.status = tsb.join('');
                }

                t.push(record_datas);
            }
        }
        return t;
    }
    removeRecordFieldValue(recordIndex, fieldIndex, params) {
        let {value, vocabularyId} = params;
        this.records[recordIndex].fields[fieldIndex].removeValue(value, vocabularyId);
    }

    addRecordFieldValue(recordIndex, fieldIndex, params) {
        let {value, merge, vocabularyId} = params;
        this.records[recordIndex].fields[fieldIndex].addValue(value, merge, vocabularyId);
    }

/*    options.recordCollection.removeRecordFieldValue(r, currentFieldId, {
    value, vocabularyId
})*/
    // currentRecord.fields[currentFieldId].removeValue(value, VocabularyId);


}
export default RecordCollection;
