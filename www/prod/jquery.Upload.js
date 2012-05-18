;
var p4 = p4 || {};

;
(function(p4, $){

    /**
     * UPLOADER MANAGER
     */
    var UploaderManager = function(options){

        var options = options || {};

        if(false === ("container" in options)){
            throw "missing container parameter";
        }
        else if(! options.container.jquery){
            throw "container parameter must be a jquery dom element";
        }

        if(false === ("settingsBox" in options)){
            throw "missing settingBox parameter";
        }
        else if(! options.settingsBox.jquery){
            throw "container parameter must be a jquery dom element";
        }

        if(false === ("uploadBox" in options)){
            throw "missing uploadBox parameter";
        }
        else if(! options.uploadBox.jquery){
            throw "container parameter must be a jquery dom element";
        }

        if(false === ("downloadBox" in options)){
            throw "missing downloadBox parameter";
        }
        else if(! options.downloadBox.jquery){
            throw "container parameter must be a jquery dom element";
        }

        this.recordClass = options.recordClass || 'upload-record';

        this.options = options;

        this.options.uploadBox.wrapInner('<ul />');

        this.options.uploadBox = this.options.uploadBox.find('ul:first');

        if($.isFunction($.fn.sortable)){
            this.options.uploadBox.sortable();
        }

        this.uploadIndex = 0;

        this.Queue = new Queue();
        this.Formater = new Formater();
        this.Preview = new Preview();
    };

    UploaderManager.prototype = {
        setOptions : function(options){
            return $.extend(this.options, options);
        },
        getContainer : function(){
            return this.options.container;
        },
        getUploadBox : function(){
            return this.options.uploadBox;
        },
        getSettingsBox : function(){
            return this.options.settingsBox;
        },
        getDownloadBox : function(){
            return this.options.downloadBox;
        },
        clearUploadBox: function(){
            this.getUploadBox().empty();
            this.uploadIndex = 0;
            this.Queue.clear();
        },
        getDatas : function(){
            return this.Queue.all();
        },
        getData : function(index){
            return this.Queue.get(index);
        },
        addData: function(data){
            this.uploadIndex++;
            data.uploadIndex = this.uploadIndex;
            this.Queue.set(this.uploadIndex, data);
        },
        removeData  : function(index){
            this.Queue.remove(index);
        },
        addAttributeToData : function(indexOfData, attribute, value){
            var data = this.getData(indexOfData);
            if($.type(attribute) === "string"){
                data[attribute] = value;
                this.Queue.set(indexOfData, data);
            }
        },
        getUploadIndex : function(){
            return this.uploadIndex;
        },
        hasData : function(){
            return !this.Queue.isEmpty();
        }
    }


    /**
     * PREVIEW
     *
     * Dependency : loadImage function
     * @see https://github.com/blueimp/JavaScript-Load-Image
     *
     * Options
     *  maxWidth: (int) Max width of preview
     *  maxHeight: (int) Max height of preview
     *  minWidth: (int) Min width of preview
     *  minHeight: (int) Min height of preview
     *  canva: (boolean) render preview as canva if supported by the navigator
     */

    var Preview = function(){
        this.options = {
            fileType: /^image\/(gif|jpeg|png|jpg)$/,
            maxSize : 5242880 // 5MB
        };
    }

    Preview.prototype = {
        setOptions: function(options){
            this.options = $.extend(this.options, options);
        },
        getOptions: function(){
            return this.options;
        },
        render: function(file, callback){
            if(typeof loadImage == 'function' && this.options.fileType.test(file.type)){
                if($.type(this.options.maxSize) !== 'number' || file.size < this.options.maxSize){
                    var options = {
                        maxWidth: this.options.maxWidth || 150,
                        maxHeight: this.options.maxHeight || 75,
                        minWidth: this.options.minWidth || 80,
                        minHeight: this.options.minHeight || 40,
                        canvas : this.options.canva || true
                    };
                    loadImage(file, callback, options);
                }
            }
        }
    }


    /**
     * FORMATER
     */

    var Formater = function(){

    }

    Formater.prototype = {
        size: function (bytes) {
            if (typeof bytes !== 'number') {
                throw bytes + ' is not a number';
            }
            if (bytes >= 1073741824) {
                return (bytes / 1073741824).toFixed(2) + ' GB';
            }
            if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + ' MB';
            }
            return (bytes / 1024).toFixed(2) + ' KB';
        },
        bitrate: function (bits) {
            if (typeof bits !== 'number') {
                throw bits + ' is not a number';
            }
            if (bits >= 1073741824) {
                return (bits / 1073741824).toFixed(2) + ' Gbit/s';
            }
            if (bits >= 1048576) {
                return (bits / 1048576).toFixed(2) + ' Mbit/s';
            }
            if (bits >= 1024) {
                return (bits / 1024).toFixed(2) + ' Kbit/s';
            }
            return bits + ' bit/s';
        },
        pourcent: function(current, total){
            return (current/ total * 100).toFixed(2)
        }
    }

    /**
     * QUEUE
     */
    var Queue = function(){
        this.list = {};
    };

    Queue.prototype = {
        all : function(){
            return this.list;
        },
        set : function(id, item){
            this.list[id] = item;
            return this;
        },
        get : function(id){
            if(!this.list[id]){
                throw 'Unknown ID' + id;
            }
            return this.list[id];
        },
        remove : function(id) {
            delete this.list[id];
        },
        getLength : function(){
            var count = 0;
            for (var k in this.list){
                if (this.list.hasOwnProperty(k)){
                    ++count;
                }
            }
            return count;
        },
        isEmpty: function(){
            return this.getLength() === 0;
        },
        clear: function(){
            var $this = this;
            $.each(this.list, function(k){
                $this.remove(k);
            });
        }
    }

    p4.UploaderManager = UploaderManager;

})(p4, jQuery);
