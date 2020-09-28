import $ from 'jquery';
(function () {
    $.fn.extend({
        highlight: function (color) {
            if ($(this).hasClass('animating')) {
                return $(this);
            }
            color = typeof color !== 'undefined' ? color : 'red';
            const oldColor = $(this).css('backgroundColor');
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
})();
