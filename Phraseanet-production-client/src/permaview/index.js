require('./style/main.scss');
import bootstrap from './bootstrap.js';

let PermaviewApplication = {
    bootstrap
};

if (typeof window !== 'undefined') {
    window.PermaviewApplication = PermaviewApplication;
}

module.exports = PermaviewApplication;
