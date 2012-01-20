
(function( window ) {
  
  var recordFieldValue = function(meta_id, value, VocabularyId) {
    
    if(typeof VocabularyId === 'undefined')
    {
      VocabularyId = null;
    }
    
    this.datas = {
      meta_id:meta_id, 
      value:value, 
      VocabularyId:VocabularyId
    };
    
    var $this = this;
  };
  
  recordFieldValue.prototype = {
    getValue : function() {
      return this.datas.value;
    },
    getMetaId : function() {
      return this.datas.meta_id;
    },
    getVocabularyId : function() {
      return this.datas.VocabularyId;
    },
    setValue : function(value) {
      this.datas.value = value;
      return this;
    },
    remove : function() {
      this.datas.value = '';
      
      return this;
    }
  };
  
  var databoxField = function(name, meta_struct_id, options) {
    
    var defaults = {
      multi : false,
      required : false,
      readonly : false,
      maxLength : null,
      minLength : null,
      type : 'string',
      separator : null,
      vocabularyControl : null,
      vocabularyRestricted : false
    },
    options = (typeof options == 'object') ? options : {};
        
    if(isNaN(meta_struct_id))
    {
      throw 'meta_struct_id should be a number';
    }
    
    this.name = name;
    this.meta_struct_id = meta_struct_id;
    this.options = jQuery.extend(defaults, options);
    
  };
  
  databoxField.prototype = {
    getMetaStructId : function() {
      return this.meta_struct_id;
    },
    getName : function() {
      return this.name;
    },
    isMulti : function() {
      return this.options.multi;
    },
    isRequired : function() {
      return this.options.required;
    },
    isReadonly : function() {
      return this.options.readonly;
    },
    getMaxLength : function() {
      return this.options.maxLength;
    },
    getMinLength : function() {
      return this.options.minLength;
    },
    getType : function() {
      return this.options.type;
    },
    getSeparator : function() {
      return this.options.separator;
    }
  };
  
  var recordField = function(databoxField, arrayValues) {
    
    this.databoxField = databoxField;
    this.options = {
      dirty : false
    };
    this.datas = new Array();

    if(typeof arrayValues === 'object')
    {
      var first = true;
      for(v in arrayValues)
      {
        if(typeof arrayValues[v] !== 'object')
        {
          if(window.console)
            console.error('Trying to add a non-recordFieldValue to the field...');
          
          continue;
        }
        
        if(isNaN(arrayValues[v].getMetaId()))
        {
          if(window.console)
            console.error('Trying to add a recordFieldValue without metaId...');
          
          continue;
        }
        
        if(!first && this.options.multi === false)
        {
          if(window.console)
            console.error('Trying to add multi values in a non-multi field');
        }
        
        if(window.console)
          console.log('adding a value : ', arrayValues[v]);

        this.datas.push(arrayValues[v]);
        first = false;
      }
    }
    
    var $this = this;
  }
  recordField.prototype = {
    getName : function() {
      return this.databoxField.getName();
    },
    isMulti : function() {
      return this.databoxField.isMulti();
    },
    isRequired : function() {
      return this.databoxField.isRequired();
    },
    isDirty : function() {
      return this.options.dirty;
    },
    addValue : function(value, merge, VocabularyId) {
      
      if(typeof VocabularyId === 'undefined')
        VocabularyId = null;
      
      merge = !!merge;
      
      if(this.databoxField.isReadonly())
      {
        if(window.console)
          console.error('Unable to set a value to a readonly field');
        return;
      }
      
      if(window.console)
      {
        console.log('adding value ',value,' vocId : ', VocabularyId , '  ; merge is ',merge);
      }
      
      if(this.isMulti())
      {
        if(!this.hasValue(value, VocabularyId))
        {
          if(window.console)
          {
            console.log('adding new multi value ',value);
          }
          this.datas.push(new recordFieldValue(null, value, VocabularyId));
          this.options.dirty = true;
        }
      }
      else
      {
        if(merge === true && this.isEmpty() === false)
        {
          if(window.console)
          {
            console.log('Merging value ',value);
          }
          this.datas[0].setValue(this.datas[0].getValue() + ' ' + value);
          this.datas[0].setVocabularyId(VocabularyId);
          
          this.options.dirty = true;
        }
        else  
        {
          if(!this.hasValue(value, VocabularyId))
          {
            if(this.datas.length === 0)
            {
              if(window.console)
              {
                console.log('Adding new value ',value);
              }
              this.datas.push(new recordFieldValue(null, value, VocabularyId));
            }
            else
            {
              if(window.console)
              {
                console.log('Updating value ',value);
              }
              this.datas[0].setValue(value);
              this.datas[0].setVocabularyId(VocabularyId);
            }
            this.options.dirty = true;
          }
        }
      }
      
      return this;
    },
    hasValue : function(value, VocabularyId) {
      
      if(typeof value === 'undefined')
      {
        if(window.console)
          console.error('Trying to check the presence of an undefined value');
      }
      
      if(typeof VocabularyId === 'undefined')
        VocabularyId = null;
      
      for(d in this.datas)
      {
        if(this.datas[d].getVocabularyId() !== null && VocabularyId !== null)
        {
          if(this.datas[d].getVocabularyId() === VocabularyId)
            return true;
        }
        else if(this.datas[d].getValue() == value)
        {
          return true;
        }
      }
      return false;
    },
    removeValue : function(value) {
      
      if(this.databoxField.isReadonly())
      {
        if(window.console)
          console.error('Unable to set a value to a readonly field');
        return;
      }
      
      for(d in this.datas)
      {
        if(this.datas[d].getValue() == value)
        {
          this.datas[d].remove();
          this.options.dirty = true;
        }
      }
      return this;
    },
    isEmpty : function() {
      var empty = true;
      
      for(d in this.datas)
      {
        if(this.datas[d].getValue() !== '')
          empty = false;
      }
      return empty;
    },
    empty : function() {
      
      if(this.databoxField.isReadonly())
      {
        if(window.console)
          console.error('Unable to set a value to a readonly field');
        return;
      }
      
      for(d in this.datas)
      {
        this.datas[d].remove();
        this.options.dirty = true;
      }
      return this;
    },
    getValue : function() {
      
      if(this.isMulti())
        throw 'This field is multi, I can not give you a single value';
      
      if(this.isEmpty())
        return null;
      
      return this.datas[0];
    },
    getValues : function() {
      
      if(!this.isMulti())
      {
        throw 'This field is not multi, I can not give you multiple values';
      }
    
      if(this.isEmpty())
        return new Array();
      
      var arrayValues = [];
      
      for(d in this.datas)
      {
        if(this.datas[d].getValue() === '')
          continue;
        
        arrayValues.push(this.datas[d]);
      }
      
      return arrayValues;
    },
    getSerializedValues : function() {

      var arrayValues = [];
      var values = this.getValues();
      
      for(v in values)
      {
        arrayValues.push(values[v].getValue());
      }
      
      return arrayValues.join(' ' + this.databoxField.getSeparator() + ' ');
    },
    replaceValue : function(search, replace) {
      
      if(this.databoxField.isReadonly())
      {
        if(window.console)
          console.error('Unable to set a value to a readonly field');
        return;
      }
      
      console.log('Search / Replace');
      
      for(d in this.datas)
      {
        if(this.datas[d].getVocabularyId() !== null)
        {
          console.log('value has vocabId, continue;');
          continue;
        }
        
        var value = this.datas[d].getValue();
        var replacedValue = value.replace(search, replace);
        
        if(value === replacedValue)
        {
          console.log('value', value, ' has not change, continue;');
          continue;
        }
        this.removeValue(value);
        
        if(!this.hasValue(replacedValue))
        {
          console.log('adding value ', replacedValue);
          this.addValue(replacedValue);
        }
        
        this.options.dirty = true;
      }
      
      /**
       * cleanup and remove duplicates
       */
      
      return this;
    }
  };

  window.p4 = window.p4 || {};
  
  window.p4.databoxField = databoxField;
  window.p4.recordFieldValue = recordFieldValue;
  window.p4.recordField = recordField;
  
})(window);