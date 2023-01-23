import $ from 'jquery';
require('./style/main.scss');
import * as utils from './../components/utils/utils.js';
import bootstrap from './bootstrap.js';
require('./../../node_modules/jquery-ui-datepicker-with-i18n/ui/i18n/jquery.ui.datepicker-ar.js');
require('./../../node_modules/jquery-ui-datepicker-with-i18n/ui/i18n/jquery.ui.datepicker-de.js');
require('./../../node_modules/jquery-ui-datepicker-with-i18n/ui/i18n/jquery.ui.datepicker-es.js');
require('./../../node_modules/jquery-ui-datepicker-with-i18n/ui/i18n/jquery.ui.datepicker-fr.js');
require('./../../node_modules/jquery-ui-datepicker-with-i18n/ui/i18n/jquery.ui.datepicker-nl.js');
require('./../../node_modules/jquery-ui-datepicker-with-i18n/ui/i18n/jquery.ui.datepicker-en-GB.js');

$.widget.bridge('uitooltip', $.fn.tooltip);
//window.btn = $.fn.button.noConflict(); // reverts $.fn.button to jqueryui btn
//$.fn.btn = window.btn; // assigns bootstrap button functionality to $.fn.btn

let ProductionApplication = {
    bootstrap, utils
};

if (typeof window !== 'undefined') {
    window.ProductionApplication = ProductionApplication;
}

module.exports = ProductionApplication;
