import bootstrap from './bootstrap';
let lightboxApplication = {
    bootstrap
};

if (typeof window !== 'undefined') {
    window.lightboxApplication = lightboxApplication;
}


$(window).on("load resize ",function(e) {
    /* See more basket btn*/
    $('.see_more_basket').on('click', function (e) {
        see_more('basket');
    });
    $('.see_more_feed').on('click', function (e) {
        see_more('feed');

    });

    function see_more(target) {
        $('.other_' + target).toggleClass('hidden');
        document.getElementById('see_more_' + target).scrollIntoView({
            behavior: 'smooth'
        });
        document.getElementById('see_less_' + target).scrollIntoView({
            behavior: 'smooth',
            block: "start"
        });
        $('.see_more_' + target).toggleClass('hidden');
    }

});
module.exports = lightboxApplication;
