var p4 = p4 || {};

var utilsModule = (function (p4) {


    function RGBtoHex(R, G, B) {
        return toHex(R) + toHex(G) + toHex(B);
    }
    function toHex(N) {
        if (N === null) return "00";
        N = parseInt(N);
        if (N === 0 || isNaN(N)) return "00";
        N = Math.max(0, N);
        N = Math.min(N, 255);
        N = Math.round(N);
        return "0123456789ABCDEF".charAt((N - N % 16) / 16)
            + "0123456789ABCDEF".charAt(N % 16);
    }
    function hsl2rgb(h, s, l) {
        var m1, m2, hue;
        var r, g, b;
        s /= 100;
        l /= 100;
        if (s === 0)
            r = g = b = (l * 255);
        else {
            if (l <= 0.5)
                m2 = l * (s + 1);
            else
                m2 = l + s - l * s;
            m1 = l * 2 - m2;
            hue = h / 360;
            r = HueToRgb(m1, m2, hue + 1 / 3);
            g = HueToRgb(m1, m2, hue);
            b = HueToRgb(m1, m2, hue - 1 / 3);
        }
        return {
            r: r,
            g: g,
            b: b
        };
    }

    function HueToRgb(m1, m2, hue) {
        var v;
        if (hue < 0)
            hue += 1;
        else if (hue > 1)
            hue -= 1;

        if (6 * hue < 1)
            v = m1 + (m2 - m1) * hue * 6;
        else if (2 * hue < 1)
            v = m2;
        else if (3 * hue < 2)
            v = m1 + (m2 - m1) * (2 / 3 - hue) * 6;
        else
            v = m1;

        return 255 * v;
    }
    return {
        RGBtoHex: RGBtoHex, hsl2rgb: hsl2rgb
    };
}(p4));
