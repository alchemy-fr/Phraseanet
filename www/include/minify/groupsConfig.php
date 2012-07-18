<?php

/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */
/**
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 * */
$groups = array(
    'client' => array(
        '//include/jslibs/swfobject/swfobject.js'
        , '//include/jslibs/jquery-ui-1.8.17/jquery-ui-i18n.js'
        , '//login/geonames.js'
        , '//include/jslibs/jquery.cookie.js'
        , '//include/jquery.common.js'
        , '//include/jslibs/json2.js'
        , '//include/jslibs/audio-player/audio-player-noswfobject.js'
        , '//include/jslibs/jquery.form.2.49.js'
        , '//client/jquery.p4client.1.0.js'
        , '//include/jquery.tooltip.js'
        , '//include/jquery.p4.preview.js'
        , '//include/jquery.image_enhancer.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'),
    'admin' => array(
         '//include/jslibs/jquery.cookie.js'
        , '//include/jslibs/jquery-treeview/jquery.treeview.js'
        , '//include/jslibs/jquery-ui-1.8.17/jquery-ui-i18n.js'
        , '//include/jquery.kb-event.js'
        , '//admin/users.js'
        , '//admin/editusers.js'
        , '//include/jquery.common.js'
        , '//login/geonames.js'
        , '//include/jquery.tooltip.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'
        , '//include/vendor/javascript-load-image/load-image.js'
        , '//include/vendor/jquery-file-upload/js/vendor/jquery.ui.widget.js'
        , '//include/vendor/jquery-file-upload/js/jquery.iframe-transport.js'
        , '//include/vendor/jquery-file-upload/js/jquery.fileupload.js'
    ),
    'report' => array(
         '//include/jslibs/jquery-ui-1.8.17/jquery-ui-i18n.js'
        , '//include/jslibs/jquery.cookie.js'
        , '//include/jquery.common.js'
        , '//include/jquery.tooltip.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'
        , '//include/jslibs/jquery.print.js'
        , '//include/jslibs/jquery.multiselect.js'
        , '//include/jslibs/jquery.cluetip.js'
        , '//include/jslibs/jquery.tablesorter.2.0.3.js'
        , '//include/jquery.nicoslider.js'
        , '//report/report.js'
    ),
    'reportmobile' => array(
         '//include/jslibs/jquery-ui-1.8.17/jquery-ui-i18n.js'
        , '//include/jslibs/jquery.cookie.js'
        , '//include/jquery.common.js'
        , '//include/jquery.tooltip.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'
        , '//include/jslibs/jquery.gvChart-0.1.js'
        , '//include/jslibs/jqtouch/jqtouch/jqtouch.js'
        , '//include/jslibs/jquery.slide-mobile.js'
        , '//report/report_mobile.js'
    ),
    'modalBox' => array(
         '//include/jslibs/jquery-ui-1.8.17/jquery-ui-i18n.js'
    ),
    'prod' => array(
        '//include/jslibs/swfobject/swfobject.js'
        , '//include/jslibs/json2.js'
        , '//include/jslibs/colorpicker/js/colorpicker.js'
        , '//include/jslibs/jquery.mousewheel.js'
        , '//include/jslibs/jquery-ui-1.8.17/jquery-ui-i18n.js'
        , '//include/jslibs/jquery.cookie.js'
        , '//include/jquery.common.js'
        , '//include/vendor/humane-js/humane.js'
        , '//include/vendor/mustache-js/mustache.js'
        , '//include/vendor/javascript-load-image/load-image.js'
        , '//include/vendor/jquery-file-upload/js/vendor/jquery.ui.widget.js'
        , '//include/vendor/jquery-file-upload/js/jquery.iframe-transport.js'
        , '//include/vendor/jquery-file-upload/js/jquery.fileupload.js'
        , '//login/geonames.js'
        , '//include/jslibs/jquery.form.2.49.js'
        , '//include/jslibs/jquery.vertical.buttonset.js'
        , '//include/js/jquery.Selection.js'
        , '//include/js/jquery.Edit.js'
        , '//include/js/jquery.lists.js'
        , '//prod/jquery.Phraseanet.js'
        , '//prod/jquery.Prod.js'
        , '//prod/jquery.Dialog.js'
        , '//prod/jquery.Feedback.js'
        , '//prod/jquery.Results.js'
        , '//prod/page0.js'
        , '//prod/jquery.WorkZone.js'
        , '//prod/jquery.Alerts.js'
        , '//prod/jquery.Upload.js'
        , '//include/jslibs/pixastic.custom.js'
        , '//prod/ThumbExtractor.js'
        , '//prod/publicator.js'
        , '//prod/jquery.order.js'
        , '//include/jslibs/jquery.sprintf.1.0.3.js'
        , '//include/jquery.tooltip.js'
        , '//include/jslibs/flowplayer/flowplayer-3.2.6.min.js'
        , '//include/jquery.p4.preview.js'
        , '//prod/jquery.edit.js'
        , '//include/jslibs/jquery.color.animation.js'
        , '//include/jquery.image_enhancer.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'
        , '//include/jslibs/jquery-treeview/jquery.treeview.js'
        , '//include/jslibs/jquery-treeview/jquery.treeview.async.js'),
    'thesaurus' => array(
         '//include/jslibs/jquery.cookie.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'
        , '//include/jquery.common.js'
        , '//thesaurus2/win.js'
        , '//thesaurus2/xmlhttp.js'
        , '//thesaurus2/thesaurus.js'
    ),
    'lightbox' => array(
         '//include/jslibs/jquery.mousewheel.js'
        , '//include/jquery.tooltip.js'
        , '//include/jslibs/swfobject/swfobject.js'
        , '//login/geonames.js'
        , '//include/jslibs/jquery-ui-1.8.17/jquery-ui-i18n.js'
        , '//include/jslibs/jquery.cookie.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'
        , '//include/jquery.common.js'
        , '//skins/lightbox/jquery.lightbox.js'
        , '//include/jslibs/flowplayer/flowplayer-3.2.6.min.js'
    ),
    'lightboxie6' => array(
         '//include/jslibs/jquery.mousewheel.js'
        , '//include/jquery.tooltip.js'
        , '//include/jslibs/swfobject/swfobject.js'
        , '//login/geonames.js'
        , '//include/jslibs/jquery-ui-1.8.17/jquery-ui-i18n.js'
        , '//include/jslibs/jquery.cookie.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'
        , '//include/jquery.common.js'
        , '//skins/lightbox/jquery.lightbox.ie6.js'
        , '//include/jslibs/flowplayer/flowplayer-3.2.6.min.js'
    )
);

return $groups;
