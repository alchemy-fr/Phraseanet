;
(function(document){

  /*****************
   * Canva Object
   *****************/
  var Canva = function(domCanva){
    this.domCanva = domCanva;
  };

  Canva.prototype = {
    resize : function(elementDomNode){
      var w = elementDomNode.getWidth();
      var maxH = elementDomNode.getHeight();

      if ('' !== elementDomNode.getAspectRatio()) {
        var ratio = parseFloat(elementDomNode.getAspectRatio());

        var h = Math.round(w * (1 / ratio));

        if (h > maxH) {
            var h = maxH;
            var w =  Math.round(h * ratio);
        }
      } else {
          var h = maxH;
      }

      this.domCanva.setAttribute("width", w);
      this.domCanva.setAttribute("height", h);

      return this;
    },
    getContext2d : function(){

      if (undefined === this.domCanva.getContext)
      {
        return G_vmlCanvasManager
        .initElement(this.domCanva)
        .getContext("2d");
      }

      return this.domCanva.getContext('2d');
    },
    extractImage : function(){
      return this.domCanva.toDataURL("image/png");
    },
    reset : function(){
      var context = this.getContext2d();
      var w = this.getWidth();
      var h =  this.getHeight();

      context.save();
      context.setTransform(1, 0, 0, 1, 0, 0);
      context.clearRect(0, 0, w, h);
      context.restore();

      return this;
    },
    copy : function(elementDomNode){
      var context = this.getContext2d();

      context.drawImage(
        elementDomNode.getDomElement()
        , 0
        , 0
        , this.getWidth()
        , this.getHeight()
        );

      return this;
    },
    getDomElement : function(){
      return this.domCanva;
    },
    getHeight : function(){
      return this.domCanva.offsetHeight;
    },
    getWidth : function(){
      return this.domCanva.offsetWidth;
    }
  };


  /******************
 *  Image Object
 ******************/
  var Image = function(domElement){
    this.domElement = domElement;
  };

  Image.prototype = {
    getDomElement : function(){
      return this.domElement;
    },
    getHeight : function(){
      return this.domElement.offsetHeight;
    },
    getWidth : function(){
      return this.domElement.offsetWidth;
    }
  };

  /******************
 *  Video Object inherits from Image object
 ******************/

  var Video = function(domElement){
    Image.call(this, domElement);
    this.aspectRatio = domElement.getAttribute('data-ratio');
  };

  Video.prototype = new Image();
  Video.prototype.constructor = Video;
  Video.prototype.getCurrentTime = function(){
    return Math.floor(this.domElement.currentTime);
  };
  Video.prototype.getAspectRatio = function(){
    return this.aspectRatio;
  };

  /******************
 *  Cache Object
 ******************/
  var Store = function(){
    this.datas = {};
  };

  Store.prototype = {
    set : function(id, item){
      this.datas[id] = item;
      return this;
    },
    get : function(id){
      if(!this.datas[id]){
        throw 'Unknown ID';
      }
      return this.datas[id];
    },
    remove : function(id) {
      delete this.datas[id];
    },
    getLength : function(){
      var count = 0;
      for (var k in this.datas){
        if (this.datas.hasOwnProperty(k)){
          ++count;
        }
      }
      return count;
    }
  };

  /******************
 *  Screenshot Object
 ******************/
  var ScreenShot = function(id, canva, video){

    var date = new Date();

    canva.resize(video);
    canva.copy(video);

    this.id = id;
    this.timestamp = date.getTime();
    this.dataURI = canva.extractImage();
    this.videoTime = video.getCurrentTime();
  };

  ScreenShot.prototype = {
    getId:function(){
      return this.id;
    },
    getDataURI: function(){
      return this.dataURI;
    },
    getTimeStamp: function(){
      return this.timestamp;
    },
    getVideoTime : function(){
      return this.videoTime;
    }
  };

  /**
 * THUMB EDITOR
 */
  var ThumbEditor = function(videoId, canvaId){

    var domElement = document.getElementById(videoId);

    if (null !== domElement) {
        var editorVideo = new Video(domElement);
    }
    var store = new Store();

    function getCanva(){
      return document.getElementById(canvaId);
    }

    return {
      isSupported : function () {
          var elem = document.createElement('canvas');

          return !! document.getElementById(videoId) && document.getElementById(canvaId)
                  && !! elem.getContext && !! elem.getContext('2d');
      },
      screenshot : function(){
        var screenshot = new ScreenShot(
          store.getLength() + 1,
          new Canva(getCanva()),
          editorVideo
          );

        store.set(screenshot.getId(), screenshot);

        return screenshot;
      },
      store : store,
      copy: function(elementDomNode){
        var element = new Image(elementDomNode);
        var editorCanva = new Canva(getCanva());
        editorCanva
        .reset()
        .resize(editorVideo)
        .copy(element);
      },
      getCanvaImage : function(){
        var canva = new Canva(getCanva());
        return canva.extractImage();
      },
      resetCanva : function(){
        var editorCanva = new Canva(getCanva());
        editorCanva.reset();
      },
      getNbScreenshot : function(){
        return store.getLength();
      }
    };
  };

  document.THUMB_EDITOR = ThumbEditor;

})(document);

