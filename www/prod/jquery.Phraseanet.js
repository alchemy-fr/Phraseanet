var p4 = p4 || {};

(function(p4){
  
  var templates = [];
  
  var LoadAndRender = function(TemplateName, datas, callback) {
    
    $.ajax({
      type: "GET",
      url: "/prod/MustacheLoader/",
      dataType: 'html',
      data: {
        template: TemplateName
      },
      success: function(data){
        templates[TemplateName] = data;
        
        return MustacheRender(TemplateName, datas, callback);
      }
    });
  }
  
  var MustacheRender = function(TemplateName, datas, callback) {
    if(templates[TemplateName])
    {
      return callback(Mustache.render(templates[TemplateName], datas));
    }
    else
      return LoadAndRender(TemplateName, datas, callback);
  };

  p4.Mustache = MustacheRender;
  
}(p4));