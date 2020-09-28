/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';
import RenewEmailForm from './common/forms/views/form';

const renewEmail = (services) => {
    const {configService, localeService, appEvents} = services;
    const initialize = () => {
        new RenewEmailForm({
            el: $('form[name=changeEmail]'),
            errorTemplate: '#field_errors_block',
            onRenderError: function (name, $el) {
                $el.closest('.control-group').addClass('error');
            },
            rules: [
                {
                    name: 'form_password',
                    rules: 'required',
                    message: localeService.t('validation_blank')
                },
                {
                    name: 'form_email',
                    rules: 'required',
                    message: localeService.t('validation_blank')
                },
                {
                    name: 'form_email',
                    rules: 'email',
                    message: localeService.t('validation_email')
                },
                {
                    name: 'form_email_confirm',
                    rules: 'matches[form_email]',
                    message: localeService.t('email_match')
                }
            ]
        });
    };


    return {initialize};
};
export default renewEmail;
