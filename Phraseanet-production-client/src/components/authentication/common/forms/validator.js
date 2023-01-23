/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';
import _ from 'underscore';
var FormValidator = function (rules, handlers) {
    // rules setted by user
    this.rules = rules || [];
    // custom callbacks
    this.handlers = handlers || [];
    // final fields to validate
    this.fields = [];

    var self = this;

    _.each(this.rules, function (field) {
        if ('name' in field && 'rules' in field) {
            self._addField(field);
        }
    });
};

// Validate method, argument is the serialize form
FormValidator.prototype.validate = function (inputs) {
    var self = this;
    // possible errors
    this.errors = [];
    // inputs present in form
    this.inputs = {};

    _.each(_.groupBy(inputs, function (input) {
        return input.name;
    }), function (fields, name) {
        self.inputs[name] = fields;
    });

    this._validateForm();

    return this;
};

FormValidator.prototype.getErrors = function () {
    return this.errors;
};

FormValidator.prototype.hasErrors = function () {
    return this.errors.length > 0;
};

FormValidator.prototype.getRules = function () {
    return this.rules;
};

FormValidator.prototype._addField = function (field) {
    this.fields.push({
        name: field.name,
        rules: field.rules,
        message: field.message || 'An error ocurred on input[name=' + field.name + '], you can edit this message by setting a "message" property in your rule definition object',
        value: null,
        type: field.type || 'text'
    });
};

FormValidator.prototype._validateForm = function () {
    var self = this;
    this.errors = [];
    _.each(this.fields, function (field) {
        if (_.has(self.inputs, field.name)) {
            // values can be multiple
            var values = [];

            _.each(self.inputs[field.name], function (field) {
                return values.push(field.value);
            });

            field.value = values.join(',');

            self._validateField(field);
        } else if (field.type === 'checkbox' || field.type === 'radio' || field.type === 'select' || field.type === 'multiple') {
            field.value = '';
            self._validateField(field);
        }
    });
};

FormValidator.prototype._validateField = function (field) {
    var self = this;
    var ruleRegex = /^(.+?)\[(.+)\]$/;
    var rules = field.rules.split('|');

    // Run through the rules and execute the validation methods as needed
    _.every(rules, function (method) {
        var param = null;
        var failed = false;
        var parts = ruleRegex.exec(method);

        // If the rule has a parameter (i.e. matches[param]) split it out
        if (parts) {
            method = parts[1];
            param = parts[2];
        }

        // If the hook is defined, run it to find any validation errors
        if (typeof self._hooks[method] === 'function') {
            if (!self._hooks[method].apply(self, [field, param])) {
                failed = true;
            }
        } else if (method.substring(0, 9) === 'callback_') {
            // Custom method. Execute the handler if it was registered
            method = method.substring(9, method.length);

            if (typeof self.handlers[method] === 'function') {
                if (this.handlers[method].apply(self, [field.value, param]) === false) {
                    failed = true;
                }
            }
        }

        // If the hook failed, add a message to the errors array
        if (failed) {
            self.errors.push({
                name: field.name,
                value: field.value,
                message: field.message,
                rule: method
            });
        }

        // Breaks loop iteration
        return failed;
    });
};

FormValidator.prototype.Regexp = {
    numericRegex: /^[0-9]+$/,
    integerRegex: /^\-?[0-9]+$/,
    decimalRegex: /^\-?[0-9]*\.?[0-9]+$/,
    emailRegex: /^[^@]+@[^@]+\.[^@]+$/,
    alphaRegex: /^[a-z]+$/i,
    alphaNumericRegex: /^[a-z0-9]+$/i,
    alphaDashRegex: /^[a-z0-9_\-]+$/i,
    naturalRegex: /^[0-9]+$/i,
    naturalNoZeroRegex: /^[1-9][0-9]*$/i,
    // IP v6 a v4 or hostname, by Mikulas Dite see http://stackoverflow.com/a/9209720/96656
    ipRegex: /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$|^(?:(?:(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):(?:(?:[0-9a-fA-F]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-fA-F]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):(?:(?:[0-9a-fA-F]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})))?::(?:(?:(?:[0-9a-fA-F]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):(?:(?:[0-9a-fA-F]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):){0,1}(?:(?:[0-9a-fA-F]{1,4})))?::(?:(?:(?:[0-9a-fA-F]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):(?:(?:[0-9a-fA-F]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):){0,2}(?:(?:[0-9a-fA-F]{1,4})))?::(?:(?:(?:[0-9a-fA-F]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):(?:(?:[0-9a-fA-F]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):){0,3}(?:(?:[0-9a-fA-F]{1,4})))?::(?:(?:[0-9a-fA-F]{1,4})):)(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):(?:(?:[0-9a-fA-F]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):){0,4}(?:(?:[0-9a-fA-F]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):(?:(?:[0-9a-fA-F]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):){0,5}(?:(?:[0-9a-fA-F]{1,4})))?::)(?:(?:[0-9a-fA-F]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-fA-F]{1,4})):){0,6}(?:(?:[0-9a-fA-F]{1,4})))?::))))$/i,
    numericDashRegex: /^[\d\-\s]+$/,
    urlRegex: /^((http|https):\/\/(\w+:{0,1}\w*@)?(\S+)|)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?$/
};

// Object containing all of the validation hooks
FormValidator.prototype._hooks = {
    required: function (field) {
        var value = field.value;

        return (value !== null && value !== '');
    },
    equal: function (field, defaultName) {
        return field.value === defaultName;
    },
    matches: function (field, matchName) {
        if (typeof this.inputs[matchName] === 'undefined') {
            return false;
        }

        var el = this.inputs[matchName].shift();

        if (el) {
            return field.value === el.value;
        }

        return false;
    },
    valid_email: function (field) {
        return this.Regexp.emailRegex.test(field.value);
    },
    valid_emails: function (field) {
        var result = field.value.split(',');

        for (var i = 0; i < result.length; i++) {
            if (!this.Regexp.emailRegex.test($.trim(result[i]))) {
                return false;
            }
        }

        return true;
    },
    min_length: function (field, length) {
        if (field.type === 'multiple') {
            return _.filter(field.value.split(','), function (value) {
                    return value !== '';
                }).length >= parseInt(length, 10)
        }

        if (!this.Regexp.numericRegex.test(length)) {
            return false;
        }

        return (field.value.length >= parseInt(length, 10));
    },
    max_length: function (field, length) {
        if (field.type === 'multiple') {
            return _.filter(field.value.split(','), function (value) {
                    return value !== '';
                }).length <= parseInt(length, 10)
        }

        if (!this.Regexp.numericRegex.test(length)) {
            return false;
        }

        return (field.value.length <= parseInt(length, 10));
    },
    exact_length: function (field, length) {
        if (field.type === 'multiple') {
            return _.filter(field.value.split(','), function (value) {
                    return value !== '';
                }).length === parseInt(length, 10)
        }

        if (!this.Regexp.numericRegex.test(length)) {
            return false;
        }

        return (field.value.length === parseInt(length, 10));
    },
    greater_than: function (field, param) {
        if (!this.Regexp.decimalRegex.test(field.value)) {
            return false;
        }

        return (parseFloat(field.value) > parseFloat(param));
    },
    less_than: function (field, param) {
        if (!this.Regexp.decimalRegex.test(field.value)) {
            return false;
        }

        return (parseFloat(field.value) < parseFloat(param));
    },
    alpha: function (field) {
        return (this.Regexp.alphaRegex.test(field.value));
    },
    alpha_numeric: function (field) {
        return (this.Regexp.alphaNumericRegex.test(field.value));
    },
    alpha_dash: function (field) {
        return (this.Regexp.alphaDashRegex.test(field.value));
    },
    numeric: function (field) {
        return (this.Regexp.numericRegex.test(field.value));
    },
    integer: function (field) {
        return (this.Regexp.integerRegex.test(field.value));
    },
    decimal: function (field) {
        return (this.Regexp.decimalRegex.test(field.value));
    },
    is_natural: function (field) {
        return (this.Regexp.naturalRegex.test(field.value));
    },
    is_natural_no_zero: function (field) {
        return (this.Regexp.naturalNoZeroRegex.test(field.value));
    },
    valid_ip: function (field) {
        return (this.Regexp.ipRegex.test(field.value));
    },
    valid_url: function (field) {
        return (this.Regexp.urlRegex.test(field.value));
    }
};

export default FormValidator;
