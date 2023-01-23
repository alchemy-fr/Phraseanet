import $ from 'jquery';
import ConfigService from './../components/core/configService';
import LocaleService from '../components/locale';
import merge from 'lodash.merge';

import defaultConfig from './config';
import Emitter from '../components/core/emitter';
import authentication from './../components/authentication';

class Bootstrap {

    app;
    configService;
    localeService;
    appServices;

    constructor(userConfig) {

        const configuration = merge({}, defaultConfig, userConfig);

        this.appEvents = new Emitter();
        this.configService = new ConfigService(configuration);
        this.localeService = new LocaleService({
            configService: this.configService
        });

        this.localeService.fetchTranslations()
            .then(() => {
                this.onConfigReady();
            });
        return this;
    }

    onConfigReady() {
        this.appServices = {
            configService: this.configService,
            localeService: this.localeService,
            appEvents: this.appEvents
        };

        // export translation for backward compatibility:
        // window.language = this.localeService.getTranslations();

        /**
         * add components
         */

        $(document).ready(() => {
            let authService = authentication(this.appServices);

            authService.initialize();

            switch (this.configService.get('state')) {
                case 'login':
                    authService.login();
                    break;
                case 'forgotPassword':
                    authService.forgotPassword();
                    break;
                case 'renewPassword':
                    authService.renewPassword();
                    break;
                case 'register':
                    authService.register();
                    break;
                case 'registerProvider':
                    authService.registerProvider();
                    break;
                case 'renewEmail':
                    authService.renewEmail();
                    break;
                case 'changePassword':
                    authService.changePassword();
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
