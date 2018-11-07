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

    var Error = function (model, fieldId, message) {
        this.model = model;
        this.fieldId = fieldId;
        this.message = message;
    };

    return Error;
});
