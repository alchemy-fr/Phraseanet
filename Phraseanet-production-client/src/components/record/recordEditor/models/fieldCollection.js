import merge from 'lodash.merge';

class FieldCollection {
    fields;

    constructor(fieldsList) {
        this.fields = fieldsList;
    }

    getFields() {
        return this.fields;
    }

    getFieldOptionsById(meta_struct_id) {
        /*var name = this.fields[meta_struct_id].name;
         var label = this.fields[meta_struct_id].label;
         var multi = ;
         var required = this.fields[meta_struct_id].required;
         var readonly = this.fields[meta_struct_id].readonly;
         var maxLength = this.fields[meta_struct_id].maxLength;
         var minLength = this.fields[meta_struct_id].minLength;
         var type = this.fields[meta_struct_id].type;
         var separator = this.fields[meta_struct_id].separator;
         var vocabularyControl = this.fields[meta_struct_id].vocabularyControl || null;
         var vocabularyRestricted = this.fields[meta_struct_id].vocabularyRestricted || null;*/

        return {
            multi: this.fields[meta_struct_id].multi,
            required: this.fields[meta_struct_id].required,
            readonly: this.fields[meta_struct_id].readonly,
            maxLength: this.fields[meta_struct_id].maxLength,
            minLength: this.fields[meta_struct_id].minLength,
            type: this.fields[meta_struct_id].type,
            separator: this.fields[meta_struct_id].separator,
            vocabularyControl: this.fields[meta_struct_id].vocabularyControl || null,
            vocabularyRestricted: this.fields[meta_struct_id].vocabularyRestricted || null
        };
    }

    setActiveField(metaStructId) {
        this.metaStructId = metaStructId;
    }

    getActiveFieldIndex() {
        return this.metaStructId === undefined ? '?' : this.metaStructId;
    }

    getActiveField() {
        return this.fields[this.getActiveFieldIndex()]
    }

    getFieldByIndex(id = false) {
        if (this.fields[id] !== undefined) {
            return this.fields[id];
        }
        return false;
    }

    getFieldByName(fieldName) {
        let foundField = false;
        for (let field in this.fields) {
            if (this.fields[field].name === fieldName) {
                foundField = this.fields[field];
            }
        }
        return foundField;
    }

    getFieldStatus(id = false) {

    }

    updateField(id, data) {
        if (this.fields[id] !== undefined) {
            this.fields[id] = merge(this.fields[id], data);
            return this.fields[id];
        }
    }

}
export default FieldCollection;
