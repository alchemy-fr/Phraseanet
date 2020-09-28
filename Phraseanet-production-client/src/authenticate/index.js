import bootstrap from './bootstrap';
let authenticateApp = {
    bootstrap
};

if (typeof window !== 'undefined') {
    window.authenticateApp = authenticateApp;
}

module.exports = authenticateApp;
