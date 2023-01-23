/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// launch application
import $ from 'jquery';
import _ from 'underscore';
import RegisterForm from './common/forms/views/form';

const registerProvider = (services) => {
    const {configService, localeService, appEvents} = services;
    const initialize = () => {
        var fieldsConfiguration = [];

        $.when.apply($, [
            $.ajax({
                url: '/login/registration-fields/',
                success: function (config) {
                    fieldsConfiguration = config;
                }
            })
        ]).done(function () {

            var rules = [];
            var defaultRules = [
                {
                    name: 'email',
                    rules: 'required',
                    message: localeService.t('validation_blank')
                },
                {
                    name: 'email',
                    rules: 'valid_email',
                    message: localeService.t('validation_email')
                },
                {
                    name: 'password',
                    rules: 'required',
                    message: localeService.t('validation_blank')
                },
                {
                    name: 'password',
                    rules: 'min_length[5]',
                    message: localeService.t('validation_length_min', {
                        postProcess: 'sprintf',
                        sprintf: ['5']
                    })
                },
                {
                    name: 'passwordConfirm',
                    rules: 'matches[password]',
                    message: localeService.t('password_match')
                },
                {
                    name: 'accept-tou',
                    rules: 'required',
                    message: localeService.t('accept_tou'),
                    type: 'checkbox'
                },
                {
                    name: 'collections[]',
                    rules: 'min_length[1]',
                    message: localeService.t('validation_choice_min', {
                        postProcess: 'sprintf',
                        sprintf: ['1']
                    }),
                    type: 'multiple'
                }
            ];

            _.each(fieldsConfiguration, function (field) {
                if (field.required) {
                    var rule = {
                        name: field.name,
                        rules: 'required',
                        message: localeService.t('validation_blank')
                    };

                    defaultRules.push(rule);
                }
            });

            _.each(defaultRules, function (rule) {
                // add rule if element exists
                if ($('[name="' + rule.name + '"]').length >= 1) {
                    rules.push(rule);
                }
            });

            new RegisterForm({
                el: $('form[name=registerForm]'),
                rules: rules
            });

        });
    };

    return {initialize};
};
export default registerProvider;
