/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "jquery",
    "underscore",
    "backbone"
], function($, _, Backbone) {


    var FormValidator = function(rules) {
        // rules setted by user
        this.rules = rules || [];
        // final fields to validate
        this.fields = [];

        var self = this;

        _.each(this.rules, function(field) {
            if ("name" in field && "rules" in field) {
                self._addField(field);
            }
        });
    };

    // Validate method, argument is the serialize form
    FormValidator.prototype.validate = function(inputs) {
        var self = this;
        // possible errors
        this.errors = [];
        // inputs present in form
        this.inputs = {};

        _.each(inputs, function(field) {
            self.inputs[field.name] = field;
        });

        this._validateForm();

        return this;
    };

    FormValidator.prototype.getErrors = function() {
        return this.errors;
    };

    FormValidator.prototype.hasErrors = function() {
        return this.errors.length > 0;
    };

    FormValidator.prototype._addField = function(field) {
        this.fields.push({
            name: field.name,
            rules: field.rules,
            message: field.message || "An error ocurred on input[name=" + field.name + "], you can edit this message by setting a 'message' property in your rule definition object",
            value: null
        });
    };

    FormValidator.prototype._validateForm = function() {
        var self = this;
        this.errors = [];
        _.each(this.fields, function(field){
            if (_.has(self.inputs, field.name)) {
                field.value = $.trim(self.inputs[field.name].value);
                self._validateField(field);
            }
        });
    };

    FormValidator.prototype._validateField = function(field) {
        var self = this;
        var ruleRegex = /^(.+?)\[(.+)\]$/;
        var rules = field.rules.split('|');

        // If the value is null and not required, we don't need to run through validation, unless the rule is a callback, but then only if the value is not null
        if ((field.rules.indexOf('required') === -1 && (!field.value || field.value === '' || typeof field.value === "undefined")) && (field.rules.indexOf('callback_') === -1 || field.value === null)) {
            return;
        }

        // Run through the rules and execute the validation methods as needed
        _.every(rules, function(method) {
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
                    id: field.id,
                    name: field.name,
                    type: field.type,
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
        numericRegex : /^[0-9]+$/,
        integerRegex : /^\-?[0-9]+$/,
        decimalRegex : /^\-?[0-9]*\.?[0-9]+$/,
        emailRegex : /^[a-zA-Z0-9.!#$%&amp,'*+\-\/=?\^_`{|}~\-]+@[a-zA-Z0-9\-]+(?:\.[a-zA-Z0-9\-]+)*$/,
        alphaRegex : /^[a-z]+$/i,
        alphaNumericRegex : /^[a-z0-9]+$/i,
        alphaDashRegex : /^[a-z0-9_\-]+$/i,
        naturalRegex : /^[0-9]+$/i,
        naturalNoZeroRegex : /^[1-9][0-9]*$/i,
        ipRegex : /^((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){3}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})$/i,
        numericDashRegex : /^[\d\-\s]+$/,
        urlRegex : /^((http|https):\/\/(\w+:{0,1}\w*@)?(\S+)|)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?$/
    };

    // Object containing all of the validation hooks
    FormValidator.prototype._hooks = {
        "required": function(field) {
            var value = field.value;

            if ((field.type === 'checkbox') || (field.type === 'radio')) {
                return (field.checked === true);
            }

            return (value !== null && value !== '');
        },
        "equal": function(field, defaultName) {
            return field.value === defaultName;
        },
        "matches": function(field, matchName) {
            var el = this.inputs[matchName];

            if (el) {
                return field.value === el.value;
            }

            return false;
        },
        "valid_email": function(field) {
            return this.Regexp.emailRegex.test(field.value);
        },
        "valid_emails": function(field) {
            var result = field.value.split(",");

            for (var i = 0; i < result.length; i++) {
                if (!this.Regexp.emailRegex.test($.trim(result[i]))) {
                    return false;
                }
            }

            return true;
        },
        "min_length": function(field, length) {
            if (!this.Regexp.numericRegex.test(length)) {
                return false;
            }

            return (field.value.length >= parseInt(length, 10));
        },
        "max_length": function(field, length) {
            if (!this.Regexp.numericRegex.test(length)) {
                return false;
            }

            return (field.value.length <= parseInt(length, 10));
        },
        "exact_length": function(field, length) {
            if (!this.Regexp.numericRegex.test(length)) {
                return false;
            }

            return (field.value.length === parseInt(length, 10));
        },
        "greater_than": function(field, param) {
            if (!this.Regexp.decimalRegex.test(field.value)) {
                return false;
            }

            return (parseFloat(field.value) > parseFloat(param));
        },
        "less_than": function(field, param) {
            if (!this.Regexp.decimalRegex.test(field.value)) {
                return false;
            }

            return (parseFloat(field.value) < parseFloat(param));
        },
        "alpha": function(field) {
            return (this.Regexp.alphaRegex.test(field.value));
        },
        "alpha_numeric": function(field) {
            return (this.Regexp.alphaNumericRegex.test(field.value));
        },
        "alpha_dash": function(field) {
            return (this.Regexp.alphaDashRegex.test(field.value));
        },
        "numeric": function(field) {
            return (this.Regexp.numericRegex.test(field.value));
        },
        "integer": function(field) {
            return (this.Regexp.integerRegex.test(field.value));
        },
        "decimal": function(field) {
            return (this.Regexp.decimalRegex.test(field.value));
        },
        "is_natural": function(field) {
            return (this.Regexp.naturalRegex.test(field.value));
        },
        "is_natural_no_zero": function(field) {
            return (this.Regexp.naturalNoZeroRegex.test(field.value));
        },
        "valid_ip": function(field) {
            return (this.Regexp.ipRegex.test(field.value));
        },
        "valid_url": function(field) {
            return (this.Regexp.urlRegex.test(field.value));
        }
    };

    return FormValidator;
});
