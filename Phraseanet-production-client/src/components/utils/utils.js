import * as AppCommons from './../../phraseanet-common';

const entityMap = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    '\'': '&#39;',
    '/': '&#x2F;'
};
const escapeHtml = function (string) {
    return String(string).replace(/[&<>"'\/]/g, (s) => {
        return entityMap[s];
    });
};
// @TODO - check legacy code
const cleanTags = function (string) {
    let chars2replace = [{
        f: '&',
        t: '&amp;'
    }, {
        f: '<',
        t: '&lt;'
    }, {
        f: '>',
        t: '&gt;'
    }];
    for (let c in chars2replace) {
        string = string.replace(RegExp(chars2replace[c].f, 'g'), chars2replace[c].t);
    }
    return string;
};

const generateRandStr = (sLength = 5) => {
    var s = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return Array(sLength).join().split(',').map(function () {
        return s.charAt(Math.floor(Math.random() * s.length));
    }).join('');
};

export {escapeHtml, cleanTags, generateRandStr};
