/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';
import loginService from './login';
import forgotPasswordService from './forgotPassword';
import renewPasswordService from './renewPassword';
import registerService from './register';
import registerProviderService from './registerProvider';
import renewEmailService from './renewEmail';
import changePasswordService from './changePassword';
import { sprintf } from 'sprintf-js';
require('bootstrap-multiselect'); // multiselect

const authentication = (services) => {
    const { configService, localeService, appEvents } = services;

    const initialize = () => {
            // close alerts
            $(document).on('click', '.alert .alert-block-close a', function (e) {
                e.preventDefault();
                $(this).closest('.alert').alert('close');
                return false;
            });

            $('select[multiple="multiple"]').multiselect({
                buttonWidth: '100%',
                buttonClass: 'btn btn-inverse',
                maxHeight: 185,
                includeSelectAllOption: true,
                selectAllValue: 'all',
                selectAllText: localeService.t('all_collections'),
                buttonText: function (options, select) {
                    if (options.length === 0) {
                        return localeService.t('no_collection_selected');
                    } else {

                        return sprintf(localeService.t(options.length === 1 ? 'one_collection_selected' : 'collections_selected'), options.length);
                    }
                }
            });
            $('form[name="registerForm"]').on('submit', function () {
                // must deselect the 'select all' checkbox for server side validation.
                $('select[multiple="multiple"]').multiselect('deselect', 'all');
            });
    };

    const login = () => {
        // init login form
        loginService(services).initialize();
    };
    const forgotPassword = () => {
        // init login form
        forgotPasswordService(services).initialize();
    };
    const renewPassword = () => {
        // init login form
        renewPasswordService(services).initialize();
    };
    const register = () => {
        // init login form
        registerService(services).initialize();
    };
    const registerProvider = () => {
        // init login form
        registerProviderService(services).initialize();
    };
    const renewEmail = () => {
        // init login form
        renewEmailService(services).initialize();
    };
    const changePassword = () => {
        // init login form
        changePasswordService(services).initialize();
    };

    return { initialize, login, forgotPassword, renewPassword, register, registerProvider, renewEmail, changePassword}
};
export default authentication;
