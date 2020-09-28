/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';
import RenewPasswordForm from './common/forms/views/formType/passwordSetter';

const changePassword = (services) => {
    const {configService, localeService, appEvents} = services;
    const initialize = () => {

        require.ensure([], () => {
            services.zxcvbn = require('zxcvbn');
            new RenewPasswordForm({
                services,
                el: $('form[name=passwordChangeForm]'),
                rules: [
                    {
                        name: 'oldPassword',
                        rules: 'required',
                        message: localeService.t('validation_blank')
                    },
                    {
                        name: 'password[password]',
                        rules: 'required',
                        message: localeService.t('validation_blank')
                    },
                    {
                        name: 'password[password]',
                        rules: 'min_length[5]',
                        message: localeService.t('validation_length_min', {
                            postProcess: 'sprintf',
                            sprintf: ['5']
                        })
                    },
                    {
                        name: 'password[confirm]',
                        rules: 'matches[password[password]]',
                        message: localeService.t('password_match')
                    }
                ]
            });

        });


    };

    return {initialize};
};
export default changePassword;
