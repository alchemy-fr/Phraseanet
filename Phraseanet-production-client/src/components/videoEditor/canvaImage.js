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

            if (h < maxH) {
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

export default Canva;
