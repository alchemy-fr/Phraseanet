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
    "models/field"
], function (_, Backbone, FieldModel) {
    var FieldCollection = Backbone.Collection.extend({
        initialize: function (models, options) {
            options = options || {};
            if (typeof options === "object" && false === "sbas_id" in options) {
                throw "You must set a sbas id"
            }
            this.sbasId = options.sbas_id;
        },
        model: FieldModel,
        url: function () {
            return "/admin/fields/" + this.sbasId + "/fields";
        },
        search: function (letters) {
            if (letters === "")
                return this;

            var pattern = new RegExp(letters, "gi");

            return _(this.filter(function (data) {
                return pattern.test(data.get("name"));
            }));
        },
        comparator: function (item) {
            return item.get("sorter");
        },
        nextIndex: function (model) {
            var index = this.indexOf(model);

            if (index < 0) {
                throw "Model not found"
            }

            if ((index + 1) === this.length) {
                return null;
            }

            return index + 1;
        },
        previousIndex: function (model) {
            var index = this.indexOf(model);

            if (index < 0) {
                throw "Model not found"
            }

            if (index === 0) {
                return null;
            }

            return index - 1;
        },
        // save all collection
        save: function (options) {
            return Backbone.sync("update", this, options || {});
        }
    });

    return FieldCollection;
});
