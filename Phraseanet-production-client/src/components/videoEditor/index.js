/**
 * Canva Object
 * @param domCanva
 * @constructor
 */
const Canva = function (domCanva) {
    this.domCanva = domCanva;
};

Canva.prototype = {
    resize: function (elementDomNode, forceWidth) {

        let w = elementDomNode.getWidth();
        let h = null;
        let maxH = elementDomNode.getHeight();
        let ratio = 1;

        if (elementDomNode.getAspectRatio() !== '') {
            ratio = parseFloat(elementDomNode.getAspectRatio());

            h = Math.round(w * (1 / ratio));

            if (h > maxH) {
                h = maxH;
                w = Math.round(h * ratio);
            }
        } else {
            h = maxH;
        }

        if (forceWidth !== undefined) {
            w = parseInt(forceWidth, 10);

            if (elementDomNode.getAspectRatio() !== '') {
                h = Math.round(w * (1 / ratio));
            } else {
                h = maxH;
            }
        }

        this.domCanva.setAttribute('width', w);
        this.domCanva.setAttribute('height', h);

        return this;
    },
    getContext2d: function () {

        if (this.domCanva.getContext === undefined) {
            /* eslint-disable no-undef */
            return G_vmlCanvasManager
                .initElement(this.domCanva)
                .getContext('2d');
        }

        return this.domCanva.getContext('2d');
    },
    extractImage: function () {
        return this.domCanva.toDataURL('image/png');
    },
    reset: function () {
        const context = this.getContext2d();
        const w = this.getWidth();
        const h = this.getHeight();

        context.save();
        context.setTransform(1, 0, 0, 1, 0, 0);
        context.clearRect(0, 0, w, h);
        context.restore();

        return this;
    },
    copy: function (elementDomNode) {
        const context = this.getContext2d();

        context.drawImage(
            elementDomNode.getDomElement()
            , 0
            , 0
            , this.getWidth()
            , this.getHeight()
        );

        return this;
    },
    getDomElement: function () {
        return this.domCanva;
    },
    getHeight: function () {
        return this.domCanva.offsetHeight;
    },
    getWidth: function () {
        return this.domCanva.offsetWidth;
    }
};


/**
 * Image Object
 * @param domElement
 * @constructor
 */
const Image = function (domElement) {
    this.domElement = domElement;
};

Image.prototype = {
    getDomElement: function () {
        return this.domElement;
    },
    getHeight: function () {
        return this.domElement.offsetHeight;
    },
    getWidth: function () {
        return this.domElement.offsetWidth;
    }
};

/**
 * Video Object inherits from Image object
 * @param domElement
 * @constructor
 */
const Video = function (domElement) {
    Image.call(this, domElement);
    this.aspectRatio = domElement.getAttribute('data-ratio');
};

Video.prototype = new Image();
Video.prototype.constructor = Video;
Video.prototype.getCurrentTime = function () {
    return Math.floor(this.domElement.currentTime);
};
Video.prototype.getAspectRatio = function () {
    return this.aspectRatio;
};

/**
 * Cache Object
 * @constructor
 */
const Store = function () {
    this.datas = {};
};

Store.prototype = {
    set: function (id, item) {
        this.datas[id] = item;
        return this;
    },
    get: function (id) {
        if (!this.datas[id]) {
            throw 'Unknown ID';
        }
        return this.datas[id];
    },
    remove: function (id) {
        // never reuse same id
        this.datas[id] = null;
    },
    getLength: function () {
        let count = 0;
        for (let k in this.datas) {
            if (this.datas.hasOwnProperty(k)) {
                ++count;
            }
        }
        return count;
    }
};

/**
 * Screenshot Object
 * @param id
 * @param canva
 * @param video
 * @param altCanvas
 * @constructor
 */
const ScreenShot = function (id, canva, video, altCanvas) {

    const date = new Date();
    canva.resize(video);
    canva.copy(video);

    // handle alternative canvas:
    altCanvas = altCanvas === undefined ? [] : altCanvas;
    this.altScreenShots = [];
    if (altCanvas.length > 0) {
        for (let i = 0; i < altCanvas.length; i++) {
            let canvaEl = altCanvas[i].el;
            canvaEl.resize(video, altCanvas[i].width);
            canvaEl.copy(video);

            this.altScreenShots.push({
                dataURI: canvaEl.extractImage(),
                name: altCanvas[i].name
            });
        }
    }

    this.id = id;
    this.timestamp = date.getTime();
    this.dataURI = canva.extractImage();
    this.videoTime = video.getCurrentTime();

};

ScreenShot.prototype = {
    getId: function () {
        return this.id;
    },
    getDataURI: function () {
        return this.dataURI;
    },
    getTimeStamp: function () {
        return this.timestamp;
    },
    getVideoTime: function () {
        return this.videoTime;
    },
    getAltScreenShots: function () {
        return this.altScreenShots;
    }
};

/**
 * THUMB EDITOR
 * @param videoId
 * @param canvaId
 * @param outputOptions
 * @returns {{isSupported: isSupported, screenshot: screenshot, store: Store, copy: copy, getCanvaImage: getCanvaImage, resetCanva: resetCanva, getNbScreenshot: getNbScreenshot}}
 * @constructor
 */
const VideoEditor = function (videoId, canvaId, outputOptions) {
    let editorVideo;
    const domElement = document.getElementById(videoId);

    if (domElement !== null) {
        editorVideo = new Video(domElement);
    }
    const store = new Store();

    function getCanva() {
        return document.getElementById(canvaId);
    }

    outputOptions = outputOptions || {};

    function setAltCanvas() {
        let domElements = [];
        let altCanvas = outputOptions.altCanvas;
        if (altCanvas.length > 0) {
            for (let i = 0; i < altCanvas.length; i++) {
                domElements.push({
                    el: new Canva(altCanvas[i]),
                    width: altCanvas[i].getAttribute('data-width'),
                    name: altCanvas[i].getAttribute('data-name')
                });
            }
        }
        return domElements;
    }

    return {
        isSupported: function () {
            const elem = document.createElement('canvas');

            return !!document.getElementById(videoId) && document.getElementById(canvaId)
                && !!elem.getContext && !!elem.getContext('2d');
        },
        screenshot: function () {
            const screenshot = new ScreenShot(
                store.getLength() + 1,
                new Canva(getCanva()),
                editorVideo,
                setAltCanvas()
            );

            store.set(screenshot.getId(), screenshot);

            return screenshot;
        },
        store: store,
        copy: function (mainSource, altSources) {

            const elementDomNode = document.createElement('img');
            elementDomNode.src = mainSource;

            const element = new Image(elementDomNode);
            const editorCanva = new Canva(getCanva());
            const altEditorCanva = setAltCanvas();
            editorCanva
                .reset()
                .resize(editorVideo)
                .copy(element);


            // handle alternative canvas:
            if (altEditorCanva.length > 0) {
                for (let i = 0; i < altEditorCanva.length; i++) {

                    let tmpEl = document.createElement('img');
                    tmpEl.src = altSources[i].dataURI;

                    const canvaEl = altEditorCanva[i].el;

                    canvaEl
                        .reset()
                        .resize(editorVideo, altEditorCanva[i].width)
                        .copy(new Image(tmpEl)); // @TODO: should copy the right stored image
                }
            }
        },
        getCanvaImage: function () {
            const canva = new Canva(getCanva());

            return canva.extractImage();
        },
        resetCanva: function () {
            const editorCanva = new Canva(getCanva());
            editorCanva.reset();
        },
        getNbScreenshot: function () {
            return store.getLength();
        }
    };
};

export default VideoEditor;
