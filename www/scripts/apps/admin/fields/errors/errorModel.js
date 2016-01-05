/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "jquery",
    "underscore"
], function ($, _) {
    var ErrorModel = function (id) {
        this.id = id;
        this.errors = {};
    };

    ErrorModel.prototype = {
        add: function (id, error) {
            if (!error instanceof Error) {
                throw "Item must be an error object";
            }

            this.errors[id] = error;
        },
        get: function (id) {
            if (this.has(id)) {
                return this.errors[id];
            }
            return null;
        },
        has: function (id) {
            return "undefined" !== typeof this.errors[id];
        },
        remove: function (id) {
            if (this.has(id)) {
                delete this.errors[id];
            }
        },
        count: function () {
            var count = 0;
            for (var k in this.errors) {
                if (this.errors.hasOwnProperty(k)) {
                    ++count;
                }
            }
            return count;
        },
        clear: function () {
            this.errors = {};
        },
        all: function () {
            return this.errors;
        }
    };

    return ErrorModel;
});
