// @TODO enable lints
/* eslint-disable camelcase*/
/* eslint-disable no-param-reassign*/
/* eslint-disable one-var*/

const utilsModule = (function () {


    function RGBtoHex(R, G, B) {
        return _toHex(R) + _toHex(G) + _toHex(B);
    }

    function _toHex(N) {
        if (N === null) {
            return '00';
        }
        let nInt = parseInt(N, 10);
        if (nInt === 0 || isNaN(nInt)) {
            return '00';
        }
        nInt = Math.max(0, nInt);
        nInt = Math.min(nInt, 255);
        nInt = Math.round(nInt);
        return '0123456789ABCDEF'.charAt((nInt - nInt % 16) / 16)
            + '0123456789ABCDEF'.charAt(nInt % 16);
    }

    function hsl2rgb(h, s, l) {
        let m1, m2, hue;
        let r, g, b;
        // s /= 100;
        // l /= 100;

        if (s === 0) {
            r = g = b = (l * 255);
        } else {
            if (l <= 0.5) {
                m2 = l * (s + 1);
            } else {
                m2 = l + s - l * s;
            }
            m1 = l * 2 - m2;
            hue = h / 360;
            r = _HueToRgb(m1, m2, hue + 1 / 3);
            g = _HueToRgb(m1, m2, hue);
            b = _HueToRgb(m1, m2, hue - 1 / 3);
        }
        return {
            r,
            g,
            b
        };
    }

    function _HueToRgb(m1, m2, hue) {
        let v;
        if (hue < 0) {
            hue += 1;
        } else if (hue > 1) {
            hue -= 1;
        }

        if (6 * hue < 1) {
            v = m1 + (m2 - m1) * hue * 6;
        } else if (2 * hue < 1) {
            v = m2;
        } else if (3 * hue < 2) {
            v = m1 + (m2 - m1) * (2 / 3 - hue) * 6;
        } else {
            v = m1;
        }

        return 255 * v;
    }

    function is_ctrl_key(event) {
        if (event.altKey) {
            return true;
        }
        if (event.ctrlKey) {
            return true;
        }
        // apple key opera
        if (event.metaKey) {
            return true;
        }
        // apple key opera
        if (event.keyCode === 17) {
            return true;
        }
        // apple key mozilla
        if (event.keyCode === 224) {
            return true;
        }
        // apple key safari
        if (event.keyCode === 91) {
            return true;
        }

        return false;
    }

    function is_shift_key(event) {
        if (event.shiftKey) {
            return true;
        }
        return false;
    }

    return {
        RGBtoHex,
        hsl2rgb,
        is_ctrl_key,
        is_shift_key
    };
}());

export default utilsModule;
