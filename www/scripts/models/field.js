/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "underscore",
    "backbone"
], function(_, Backbone) {
    var FieldModel = Backbone.Model.extend({
        initialize : function(attributes, options) {
            attributes = attributes || {};
            if (typeof attributes === "object" && false === "sbas-id" in attributes) {
                throw "You must set a sbas id";
            }
        },
        urlRoot: function () {
            return "/admin/fields/"+ this.get("sbas-id") +"/fields";
        },
        defaults: {
            "business": false,
            "type": "string",
            "thumbtitle": "0",
            "tbranch": "",
            "separator": "",
            "required": false,
            "report": true,
            "readonly": false,
            "multi": false,
            "indexable": true,
            "dces-element": null,
            "vocabulary-type": null,
            "vocabulary-restricted": false
        }
    });

    // Return the model for the module
    return FieldModel;
});
