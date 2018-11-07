/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define([
    "underscore",
    "backbone",
    "models/dcField"
], function (_, Backbone, DcFieldModel) {
    var DcFieldCollection = Backbone.Collection.extend({
        model: DcFieldModel,
        url: function () {
            return "/admin/fields/dc-fields";
        },
        comparator: function (item) {
            return item.get("label");
        }
    });

    return DcFieldCollection;
});
