<?php

/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */
/**
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 * */
$groups = [
    'authentication_css' => [
        '//assets/normalize-css/normalize.css',
        '//assets/build/login.css',
        '//assets/font-awesome/css/font-awesome.css',
        '//assets/jquery.ui/themes/base/jquery.ui.autocomplete.css'
    ],
    'authentication' => [
        '//assets/modernizr/modernizr.js',
        '//assets/requirejs/require.js',
        '//scripts/apps/login/home/config.js'
    ],
    'client' => [
        '//assets/swfobject/swfobject.js'
        , '//assets/jquery.ui/i18n/jquery-ui-i18n.js'
        , '//assets/jquery.cookie/jquery.cookie.js'
        , '//include/jquery.common.js'
        , '//assets/json2/json2.js'
        , '//include/jslibs/audio-player/audio-player-noswfobject.js'
        , '//include/jslibs/jquery.form.2.49.js'
        , '//skins/prod/jquery.Dialog.js'
        , '//skins/client/jquery.p4client.1.0.js'
        , '//include/jquery.tooltip.js'
        , '//include/jquery.p4.preview.js'
        , '//include/jquery.image_enhancer.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'],
    'admin' => [
        '//assets/modernizr/modernizr.js'
        , '//assets/underscore-amd/underscore.js'
        , '//assets/jquery.cookie/jquery.cookie.js'
        , '//assets/jquery.treeview/jquery.treeview.js'
        , '//assets/jquery.ui/i18n/jquery-ui-i18n.js'
        , '//include/jquery.kb-event.js'
        , '//skins/admin/users.js'
        , '//skins/admin/template-dialogs.js'
        , '//skins/admin/editusers.js'
        , '//include/jquery.common.js'
        , '//include/jquery.tooltip.js'
        , '//skins/prod/jquery.Dialog.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'
        , '//assets/blueimp-load-image/load-image.js'
        , '//assets/jquery-file-upload/jquery.iframe-transport.js'
        , '//assets/jquery-file-upload/jquery.fileupload.js'
        , '//assets/autobahnjs/autobahn.js'
        , '//assets/swfobject/swfobject.js'
        , '//assets/web-socket-js/web_socket.js'
    ],
    'report' => [
         '//assets/jquery.ui/i18n/jquery-ui-i18n.js'
        , '//assets/jquery.cookie/jquery.cookie.js'
        , '//include/jquery.common.js'
        , '//include/jquery.tooltip.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'
        , '//include/jslibs/jquery.print.js'
        , '//include/jslibs/jquery.cluetip.js'
        , '//include/jquery.nicoslider.js'
        , '//skins/report/report.js'
    ],
    'modalBox' => [
         '//assets/jquery.ui/i18n/jquery-ui-i18n.js'
    ],
    'prod' => [
        '//assets/swfobject/swfobject.js'
        , '//assets/underscore-amd/underscore.js'
        , '//assets/json2/json2.js'
        , '//include/jslibs/colorpicker/js/colorpicker.js'
        , '//include/jslibs/jquery.mousewheel.js'
        , '//include/jslibs/jquery.lazyload/jquery.lazyload.1.8.1.js'
        , '//assets/jquery.ui/i18n/jquery-ui-i18n.js'
        , '//assets/jquery.cookie/jquery.cookie.js'
        , '//include/jquery.common.js'
        , '//assets/humane-js/humane.js'
        , '//assets/blueimp-load-image/load-image.js'
        , '//assets/jquery-file-upload/jquery.iframe-transport.js'
        , '//assets/jquery-file-upload/jquery.fileupload.js'
        , '//include/jslibs/jquery.form.2.49.js'
        , '//include/jslibs/jquery.vertical.buttonset.js'
        , '//include/js/jquery.Selection.js'
        , '//include/js/jquery.Edit.js'
        , '//include/js/jquery.lists.js'
        , '//skins/prod/jquery.Prod.js'
        , '//skins/prod/jquery.Dialog.js'
        , '//skins/prod/jquery.Feedback.js'
        , '//skins/prod/jquery.Results.js'
        , '//skins/prod/jquery.main-prod.js'
        , '//skins/prod/jquery.WorkZone.js'
        , '//skins/prod/jquery.Alerts.js'
        , '//skins/prod/jquery.Upload.js'
        , '//include/jslibs/pixastic.custom.js'
        , '//skins/prod/ThumbExtractor.js'
        , '//skins/prod/publicator.js'
        , '//include/jslibs/jquery.sprintf.1.0.3.js'
        , '//include/jquery.tooltip.js'
        , '//include/jslibs/flowplayer/flowplayer-3.2.13.min.js'
        , '//include/jquery.p4.preview.js'
        , '//skins/prod/jquery.edit.js'
        , '//include/jslibs/jquery.color.animation.js'
        , '//include/jquery.image_enhancer.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'
        , '//assets/jquery.treeview/jquery.treeview.js'
        , '//assets/jquery.treeview/jquery.treeview.async.js'],
    'thesaurus' => [
         '//assets/jquery.cookie/jquery.cookie.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'
        , '//include/jquery.common.js'
        , '//skins/thesaurus/win.js'
        , '//skins/thesaurus/xmlhttp.js'
        , '//skins/thesaurus/thesaurus.js'
        , '//skins/thesaurus/sprintf.js'
    ],
    'lightbox' => [
         '//include/jslibs/jquery.mousewheel.js'
        , '//include/jquery.tooltip.js'
        , '//assets/swfobject/swfobject.js'
        , '//assets/jquery.ui/i18n/jquery-ui-i18n.js'
        , '//assets/jquery.cookie/jquery.cookie.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'
        , '//include/jquery.common.js'
        , '//skins/prod/jquery.Dialog.js'
        , '//skins/lightbox/jquery.lightbox.js'
        , '//include/jslibs/flowplayer/flowplayer-3.2.13.min.js'
    ],
    'lightboxie6' => [
         '//include/jslibs/jquery.mousewheel.js'
        , '//include/jquery.tooltip.js'
        , '//assets/swfobject/swfobject.js'
        , '//assets/jquery.ui/i18n/jquery-ui-i18n.js'
        , '//assets/jquery.cookie/jquery.cookie.js'
        , '//include/jslibs/jquery.contextmenu_scroll.js'
        , '//include/jquery.common.js'
        , '//skins/prod/jquery.Dialog.js'
        , '//skins/lightbox/jquery.lightbox.ie6.js'
        , '//include/jslibs/flowplayer/flowplayer-3.2.13.min.js'
    ],
    'uploadflash' => [
        '//include/jslibs/SWFUpload/swfupload.js'
        , '//include/jslibs/SWFUpload/plugins/swfupload.queue.js'
    ]
];

return $groups;
