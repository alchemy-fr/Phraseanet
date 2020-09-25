import $ from 'jquery';
import ConfigService from './../components/core/configService';
import LocaleService from '../components/locale';
import merge from 'lodash.merge';
import defaultConfig from './config';
import Emitter from '../components/core/emitter';
import account from './../components/account';

$(document).ready(() => {
// hide or show callback url input whether user choose a web or dektop application
    $('#form_create input[name=type]').bind('click', function () {
        if ($(this).val() === 'desktop') {
            $('#form_create .callback-control-group').hide().find('input').val('');
        } else {
            $('#form_create .callback-control-group').show();
        }
    });
});


class Bootstrap {

    app;
    configService;
    localeService;
    appServices;

    constructor(userConfig) {
        const configuration = merge({}, defaultConfig, userConfig);

        this.appEvents = new Emitter();
        this.configService = new ConfigService(configuration);
        this.onConfigReady();

        return this;
    }

    onConfigReady() {
        this.appServices = {
            configService: this.configService,
            localeService: this.localeService,
            appEvents: this.appEvents
        };

        /**
         * add components
         */

        $(document).ready(() => {
            let accountService = account(this.appServices);

            accountService.initialize({
                $container: $('body')
            });

            switch (this.configService.get('state')) {
                case 'editAccount':
                    accountService.editAccount();
                    break;
                case 'editSession':
                    accountService.editSession();
                    break;
                default:
            }
        });

    }
}

const bootstrap = (userConfig) => {
    return new Bootstrap(userConfig);
};

export default bootstrap;
