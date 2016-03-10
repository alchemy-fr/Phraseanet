var p4 = p4 || {};
var baskAjax, baskAjaxrunning;
baskAjaxrunning = false;
var answAjax, answAjaxrunning;
answAjaxrunning = false;
var searchAjax, searchAjaxRunning;
searchAjaxRunning = false;
var bodySize = {
    x: 0,
    y: 0
};

var prodModule = (function (p4) {
    console.log('prod')
    p4.preview = {
        open: false,
        current: false
    };


    /*function removeFromBasket(el, confirm) {
        var confirm = confirm || false;
        p4.WorkZone.removeElementFromBasket(el, confirm);
    }

    return {
        removeFromBasket: removeFromBasket
    }*/
})(p4);

//var language = {}; // handled with external prodution module



(function ($) {
    $.fn.extend({
        highlight: function (color) {
            if ($(this).hasClass('animating')) {
                return;
            }
            color = typeof color !== 'undefined' ? color : 'red';
            var oldColor = $(this).css('backgroundColor');
            return $(this).addClass('animating').stop().animate({
                backgroundColor: color
            }, 50, 'linear', function () {
                $(this).stop().animate({
                    backgroundColor: oldColor
                }, 450, 'linear', function () {
                    $(this).removeClass('animating');
                });
            });
        }
    });
})(jQuery);
