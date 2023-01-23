import bootstrap from './bootstrap';
let accountApp = {
    bootstrap
};

if (typeof window !== 'undefined') {
    window.accountApp = accountApp;
}

module.exports = accountApp;
