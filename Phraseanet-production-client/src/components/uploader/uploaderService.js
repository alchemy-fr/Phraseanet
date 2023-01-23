import $ from 'jquery';
let loadImage = require('blueimp-load-image/js/load-image');
/* The jQuery UI widget factory, can be omitted if jQuery UI is already included */
require('imports-loader?$=jquery!blueimp-file-upload/js/vendor/jquery.ui.widget.js');
/* The Iframe Transport is required for browsers without support for XHR file uploads */
require('imports-loader?define=>false&exports=>false&$=jquery!blueimp-file-upload/js/jquery.iframe-transport.js');
/* The basic File Upload plugin */
require('imports-loader?define=>false&exports=>false&$=jquery!blueimp-file-upload/js/jquery.fileupload.js');

/**
 * UPLOADER MANAGER
 */
var UploaderManager = function (options) {
    options = options || {};

    if ('container' in options === false) {
        throw 'missing container parameter';
    } else if (!options.container.jquery) {
        throw 'container parameter must be a jquery dom element';
    }

    if ('settingsBox' in options === false) {
        throw 'missing settingBox parameter';
    } else if (!options.settingsBox.jquery) {
        throw 'container parameter must be a jquery dom element';
    }

    if ('uploadBox' in options === false) {
        throw 'missing uploadBox parameter';
    } else if (!options.uploadBox.jquery) {
        throw 'container parameter must be a jquery dom element';
    }

    if ('downloadBox' in options === false) {
        throw 'missing downloadBox parameter';
    } else if (!options.downloadBox.jquery) {
        throw 'container parameter must be a jquery dom element';
    }

    this.recordClass = options.recordClass || 'upload-record';

    this.options = options;

    this.options.uploadBox.wrapInner('<ul class="thumbnails" />');

    this.options.uploadBox = this.options.uploadBox.find('ul:first');

    this.options.downloadBox.wrapInner('<ul class="thumbnails" />');

    this.options.downloadBox = this.options.downloadBox.find('ul:first');

    if ($.isFunction($.fn.sortable)) {
        this.options.uploadBox.sortable();
    }

    this.uploadIndex = 0;

    this.Queue = new Queue();
    this.Formater = new Formater();
    this.Preview = new Preview();
};

UploaderManager.prototype = {
    setOptions: function (options) {
        return $.extend(this.options, options);
    },
    getContainer: function () {
        return this.options.container;
    },
    getUploadBox: function () {
        return this.options.uploadBox;
    },
    getSettingsBox: function () {
        return this.options.settingsBox;
    },
    getDownloadBox: function () {
        return this.options.downloadBox;
    },
    clearUploadBox: function () {
        this.getUploadBox().empty();
        this.uploadIndex = 0;
        this.Queue.clear();
    },
    getDatas: function () {
        return this.Queue.all();
    },
    getData: function (index) {
        return this.Queue.get(index);
    },
    addData: function (data) {
        this.uploadIndex++;
        data.uploadIndex = this.uploadIndex;
        this.Queue.set(this.uploadIndex, data);
    },
    removeData: function (index) {
        this.Queue.remove(index);
    },
    addAttributeToData: function (indexOfData, attribute, value) {
        var data = this.getData(indexOfData);
        if ($.type(attribute) === 'string') {
            data[attribute] = value;
            this.Queue.set(indexOfData, data);
        }
    },
    getUploadIndex: function () {
        return this.uploadIndex;
    },
    hasData: function () {
        return !this.Queue.isEmpty();
    },
    countData: function () {
        return this.Queue.getLength();
    }
};
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

var Preview = function () {
    this.options = {
        fileType: /^image\/(gif|jpeg|png|jpg)$/,
        maxSize: 5242880 // 5MB
    };
};

Preview.prototype = {
    setOptions: function (options) {
        this.options = $.extend(this.options, options);
    },
    getOptions: function () {
        return this.options;
    },
    render: function (file, callback) {
        if (
            typeof loadImage === 'function' &&
            this.options.fileType.test(file.type)
        ) {
            if (
                $.type(this.options.maxSize) !== 'number' ||
                file.size < this.options.maxSize
            ) {
                var options = {
                    maxWidth: this.options.maxWidth || 150,
                    maxHeight: this.options.maxHeight || 75,
                    minWidth: this.options.minWidth || 80,
                    minHeight: this.options.minHeight || 40,
                    canvas: this.options.canva || true
                };
                loadImage(file, callback, options);
            }
        }
    }
};

/**
 * FORMATER
 */

var Formater = function () {};

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
        // 1 byte = 8 bits
        var bytes = bits >> 3;

        if (bytes >= 1 << 30) {
            return (bytes / (1 << 30)).toFixed(2) + ' Go/s';
        }
        if (bytes >= 1 << 20) {
            return (bytes / (1 << 20)).toFixed(2) + ' Mo/s';
        }
        if (bytes >= 1 << 10) {
            return (bytes / (1 << 10)).toFixed(2) + ' Ko/s';
        }
        return bytes + ' o/s';
    },
    pourcent: function (current, total) {
        return (current / total * 100).toFixed(2);
    }
};

/**
 * QUEUE
 */
var Queue = function () {
    this.list = {};
};

Queue.prototype = {
    all: function () {
        return this.list;
    },
    set: function (id, item) {
        this.list[id] = item;
        return this;
    },
    get: function (id) {
        if (!this.list[id]) {
            throw 'Unknown ID' + id;
        }
        return this.list[id];
    },
    remove: function (id) {
        delete this.list[id];
    },
    getLength: function () {
        var count = 0;
        for (let k in this.list) {
            if (this.list.hasOwnProperty(k)) {
                ++count;
            }
        }
        return count;
    },
    isEmpty: function () {
        return this.getLength() === 0;
    },
    clear: function () {
        var $this = this;
        $.each(this.list, function (k) {
            $this.remove(k);
        });
    }
};

export default UploaderManager;
