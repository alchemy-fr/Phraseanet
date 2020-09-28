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
import RegisterForm from './common/forms/views/formType/passwordSetter';
import geonames from './common/geonames';

const regiser = (services) => {
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
                }
            });

            var $form = $('form[name=registerForm]');

            require.ensure([], () => {
                services.zxcvbn = require('zxcvbn');
                new RegisterForm({
                    el: $form,
                    rules: rules,
                    services
                });
            });

            var geocompleter = geonames.init($('#geonameid'), {
                server: $form.data('geonames-server-adress'),
                limit: 40,
                'init-input': false,
                onInit: function (input, autoinput) {
                    // Set default name to geonameid-completer
                    autoinput.prop('name', 'geonameid-completer');
                }
            });

            // Positioning menu below input
            geocompleter.geocompleter('autocompleter', 'option', 'position', {
                of: geocompleter.closest('.input-table'),
                my: 'left top',
                at: 'left bottom'
            });

            // On open menu calculate max-width
            geocompleter.geocompleter('autocompleter', 'on', 'autocompleteopen', function (event, ui) {
                $(this).autocomplete('widget').css('min-width', geocompleter.closest('.input-table').outerWidth());
            });

        });
    };

    return {initialize};
};
export default regiser;
