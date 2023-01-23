/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';
import ForgotPassWordForm from './common/forms/views/form';

const forgotPassword = (services) => {
    const { configService, localeService, appEvents } = services;
    const initialize = () => {
        new ForgotPassWordForm({
            el: $('form[name=forgottenPasswordForm]'),
            rules: [
                {
                    name: 'email',
                    rules: 'required',
                    message: localeService.t('validation_blank')
                },
                {
                    name: 'email',
                    rules: 'valid_email',
                    message: localeService.t('validation_email')
                }
            ]
        });
    };

    return {initialize};
};
export default forgotPassword;

