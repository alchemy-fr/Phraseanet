import bootstrap from './bootstrap';
let lightboxMobileApplication = {
    bootstrap
};

if (typeof window !== 'undefined') {
    window.lightboxMobileApplication = lightboxMobileApplication;
}

/*resize of PDF */
$(window).on("load resize ",function(e){
    if($('.pdf-iframe').length > 0) {
        var pdfHeight =  $('.pdf-iframe').width() / 0.707;
        $('.pdf-iframe').css('height', pdfHeight);
    }
});
/*resize of VIDEO */
$(window).on("load resize ",function(e){
    if($('.video-iframe').length > 0) {

        var $sel = $('.center-image');
        var $window =  $(window).height();

        // V is for "video" ; K is for "container" ; N is for "new"
        var VH = $('[name=videoHeight]').val();
        var VW = $('[name=videoWidth]').val();
        var KW = $sel.width();
        var KH = $sel.height();

        if ($window <=375) {
            KH = 150 ;
        } else {
            if ( $window > 375 && $window <=480) {
                KH = 200 ;
            }
            if ($window > 480 && $window <=640) {
                KH = 300 ;
            }

            if ( $window > 640 && $window <=767) {
                KH = 400 ;
            }
            if ($window > 767) {
                KH =  550 ;
            }
        }

        var NW, NH;
        if( (NH = (VH / VW) * (NW=KW) ) > KH )  {   // try to fit exact horizontally, adjust vertically
            // too bad... new height overflows container height
            NW = (VW / VH) * (NH=KH);      // so fit exact vertically, adjust horizontally
        }
        $(".video-iframe", $sel).css('width', NW).css('height', NH);

    }
});

module.exports = lightboxMobileApplication;
