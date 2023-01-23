/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';
import LoginForm from './common/forms/views/form';

const login = (services) => {
    const {configService, localeService, appEvents} = services;
    const initialize = () => {

        new LoginForm({
            el: $('form[name=loginForm]'),
            rules: [
                {
                    name: 'login',
                    rules: 'required',
                    message: localeService.t('validation_blank')
                },
                {
                    name: 'password',
                    rules: 'required',
                    message: localeService.t('validation_blank')
                }
            ]
        });
    };

    return {initialize};
};
export default login;

