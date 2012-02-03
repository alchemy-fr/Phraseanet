var p4 = p4 || {};

(function( window, p4, $ ) {

  var Lists = function() {
    
  };
  
  var List = function (id) {
    
    if(parseInt(id) <= 0)
    {
      throw 'Invalid list id';
    }
      
    this.id = id;
  };
  
  Lists.prototype = {
    create : function(name, callback){

      var $this = this;

      $.ajax({
        type: 'POST',
        url: '/prod/lists/list/',
        dataType: 'json',
        data: {name : name},
        success: function(data){
          if(data.success)
          {
            humane.info(data.message);
            
            if(typeof callback === 'function')
            {
              var list = new List(data.list_id);
              callback(list);
            }
          }
          else
          {
            humane.error(data.message);
          }
        }
      });
      
    },
    get : function(callback, type) {

      var $this = this;
      type = typeof type === 'undefined' ? 'json' : type;

      $.ajax({
        type: 'GET',
        url: '/prod/lists/all/',
        dataType: type,
        data: {},
        success: function(data){
          if(type == 'json')
          {
            if(data.success)
            {
              humane.info(data.message);

              if(typeof callback === 'function')
              {
                callback(data.result);
              }
            }
            else
            {
              humane.error(data.message);
            }
          }
          else
          {
            if(typeof callback === 'function')
            {
              callback(data);
            }
          }
        }
      });
    }
    
  }
  
  List.prototype = {
    addUsers : function(arrayUsers, callback) {
      
      if(!arrayUsers instanceof Array)
      {
        throw 'addUsers takes array as argument';
      }
      
      var $this = this;
      var data = {usr_ids : $(arrayUsers).toArray()};
      
      $.ajax({
        type: 'POST',
        url: '/prod/lists/list/' + $this.id + '/add/',
        dataType: 'json',
        data: data,
        success: function(data){
          if(data.success)
          {
            humane.info(data.message);
            
            if(typeof callback === 'function')
            {
              callback($this, data);
            }
          }
          else
          {
            humane.error(data.message);
          }
        }
      });
    },
    addUser : function(usr_id, callback) {
      this.addUsers([usr_id], callback);
    },
    remove : function(callback) {
      
      var $this = this;

      $.ajax({
        type: 'POST',
        url: '/prod/lists/list/' + this.id + '/delete/',
        dataType: 'json',
        data: {},
        success: function(data){
          if(data.success)
          {
            humane.info(data.message);
            
            if(typeof callback === 'function')
            {
              callback($this);
            }
          }
          else
          {
            humane.error(data.message);
          }
        }
      });
    },
    update : function(name, callback) {
      
      var $this = this;

      $.ajax({
        type: 'POST',
        url: '/prod/lists/list/' + this.id + '/update/',
        dataType: 'json',
        data: { name : name },
        success: function(data){
          if(data.success)
          {
            humane.info(data.message);
            
            if(typeof callback === 'function')
            {
              callback($this);
            }
          }
          else
          {
            humane.error(data.message);
          }
        }
      });
    },
    removeUser : function(usr_id, callback) {
      
      var $this = this;

      $.ajax({
        type: 'POST',
        url: '/prod/lists/list/' + this.id + '/remove/' + usr_id + '/',
        dataType: 'json',
        data: {},
        success: function(data){
          if(data.success)
          {
            humane.info(data.message);
            
            if(typeof callback === 'function')
            {
              callback($this, data);
            }
          }
          else
          {
            humane.error(data.message);
          }
        }
      });
    },
    shareWith : function(usr_id, role, callback) {
      
      var $this = this;

      $.ajax({
        type: 'POST',
        url: '/prod/lists/list/' + this.id + '/share/' + usr_id + '/',
        dataType: 'json',
        data: {role : role},
        success: function(data){
          if(data.success)
          {
            humane.info(data.message);
            
            if(typeof callback === 'function')
            {
              callback($this);
            }
          }
          else
          {
            humane.error(data.message);
          }
        }
      });
    },
    unshareWith : function(callback) {
      
      var $this = this;

      $.ajax({
        type: 'POST',
        url: '/prod/lists/list/' + this.id + '/unshare/' + usr_id + '/',
        dataType: 'json',
        data: {},
        success: function(data){
          if(data.success)
          {
            humane.info(data.message);
            
            if(typeof callback === 'function')
            {
              callback($this);
            }
          }
          else
          {
            humane.error(data.message);
          }
        }
      });
    },
    get : function(callback) {
      
      var $this = this;

      $.ajax({
        type: 'GET',
        url: '/prod/lists/list/' + this.id + '/',
        dataType: 'json',
        data: {},
        success: function(data){
          if(data.success)
          {
            humane.info(data.message);
            
            if(typeof callback === 'function')
            {
              callback($this, data);
            }
          }
          else
          {
            humane.error(data.message);
          }
        }
      });
    }
  }
  
  p4.Lists = new Lists();
  document.List = List;

})(document, p4, jQuery);