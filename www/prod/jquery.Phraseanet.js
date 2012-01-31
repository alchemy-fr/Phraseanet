var p4 = p4 || {};

(function(p4, $){
  
  var templates = [];
  
  var waitStack = [];
  
  var LoadAndRender = function(TemplateName, datas, callback) {
    
    
    if(waitStack[TemplateName] instanceof Array)
    {
      waitStack[TemplateName].push({ datas : datas, callback : callback });
      return;
    }
    else
    {
      waitStack[TemplateName] = [];
    }
  
    $.ajax({
      type: "GET",
      url: "/prod/MustacheLoader/",
      dataType: 'html',
      data: {
        template: TemplateName
      },
      success: function(data){
        templates[TemplateName] = data;
        
        MustacheRender(TemplateName, datas, callback);
        
        for(s in waitStack[TemplateName])
        {
          MustacheRender(TemplateName, waitStack[TemplateName][s].datas, waitStack[TemplateName][s].callback);
        }
        
        waitStack[TemplateName] = null;
        
        return;
      }
    });
  }
  
  var MustacheRender = function(TemplateName, datas, callback) {
    if(templates[TemplateName])
    {
      var rendered = Mustache.render(templates[TemplateName], datas);
      
      if(typeof callback === 'function')
        return callback(rendered);
      else
        return rendered;
    }
    else
      return LoadAndRender(TemplateName, datas, callback);
  };
  
  var ClearCache = function() {
    templates = new Array();
  };

  p4.Mustache = {
    Render : MustacheRender,
    ClearCache : ClearCache
  };
  
}(p4, jQuery));