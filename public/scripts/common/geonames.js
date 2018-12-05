;
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define(['jquery.geonames'], factory);
    } else {
        root.geonames = factory();
    }
}(this, function () {
    return {
        init: function ($field, options) {
            var geocompleter = $field.geocompleter(options);

            // On focus add select-state
            geocompleter.geocompleter("autocompleter", "on", "autocompletefocus", function (event, ui) {
                $("li", $(event.originalEvent.target)).closest("li").removeClass("selected");
                $("a.ui-state-active, a.ui-state-hover, a.ui-state-focus", $(event.originalEvent.target)).closest("li").addClass("selected");
            });

            // On search request add loading-state
            geocompleter.geocompleter("autocompleter", "on", "autocompletesearch", function (event, ui) {
                $(this).addClass('input-loading');
                $(this).removeClass('input-error');
            });

            // On response remove loading-state
            geocompleter.geocompleter("autocompleter", "on", "autocompleteresponse", function (event, ui) {
                $(this).removeClass('input-loading');
            });

            // On close menu remove loading-state
            geocompleter.geocompleter("autocompleter", "on", "autocompleteclose", function (event, ui) {
                $(this).removeClass('input-loading');
            });

            // On request error add error-state
            geocompleter.geocompleter("autocompleter", "on", "geotocompleter.request.error", function (jqXhr, status, error) {
                $(this).removeClass('input-loading');
                $(this).addClass('input-error');
            });

            return geocompleter;
        }
    };
}));
