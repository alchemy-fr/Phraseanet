
(function( window ) {

  
  var recordFieldValue = function(meta_id, value) {
    
    this.datas = {meta_id:meta_id, value:value};
    
    var $this = this;
  }
  
  recordFieldValue.prototype = {
    getValue : function() {
      return this.datas.value;
    },
    getMetaId : function() {
      return this.datas.meta_id;
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
  
  var recordField = function(name, meta_struct_id, options, arrayValues) {
    
    var defaults = {
          name : name,
          multi : false,
          required : false,
          dirty : false
        },
        options = (typeof options == 'object') ? options : {};
    
    if(isNaN(meta_struct_id))
    {
      throw 'meta_struct_id should be a number';
    }
    
    this.meta_struct_id = meta_struct_id;
    this.options = jQuery.extend(defaults, options);
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
      return this.options.name;
    },
    isMulti : function() {
      return this.options.multi;
    },
    isRequired : function() {
      return this.options.required;
    },
    isDirty : function() {
      return this.options.dirty;
    },
    addValue : function(value, merge) {
      merge = !!merge;
      
      if(window.console)
      {
        console.log('adding value ',value,' ; merge is ',merge);
      }
      
      if(this.isMulti())
      {
        if(!this.hasValue(value))
        {
          if(window.console)
          {
            console.log('adding new multi value ',value);
          }
          this.datas.push(new recordFieldValue(null, value));
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
          this.options.dirty = true;
        }
        else  
        {
          if(!this.hasValue(value))
          {
            if(this.datas.length === 0)
            {
              if(window.console)
              {
                console.log('Adding new value ',value);
              }
              this.datas.push(new recordFieldValue(null, value));
            }
            else
            {
              if(window.console)
              {
                console.log('Updating value ',value);
              }
              this.datas[0].setValue(value);
            }
            this.options.dirty = true;
          }
        }
      }
      
      return this;
    },
    hasValue : function(value) {
      
      if(typeof value === 'undefined')
      {
        if(window.console)
          console.error('Trying to check the presence of an undefined value');
      }
      
      for(d in this.datas)
      {
        if(this.datas[d].getValue() == value)
          return true;
      }
      return false;
    },
    removeValue : function(value) {
      
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
        throw 'This field is not multi, I can not give you multiple values';
      
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
      
      return arrayValues.join(' ; ');
    },
    replaceValue : function(search, replace) {
      
      for(d in this.datas)
      {
        var value = this.datas[d].getValue();
        var replacedValue = value.replace(search, replace);
        
        if(value === replacedValue)
          continue;
        
        this.removeValue(value);
        if(!this.hasValue(replacedValue))
        {
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

  window.p4.recordFieldValue = recordFieldValue;
  window.p4.recordField = recordField;
  
})(window);