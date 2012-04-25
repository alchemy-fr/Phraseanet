<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();

phrasea::headers();
?>
<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
        <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
        <script type="text/javascript">
            var needrefresh = false;
            var oMyObject = parent.window.dialogArguments;
            var myOpener  = oMyObject.myOpener;
            function imp0rloadusr()
            {
                myOpener.document.forms[0].action = "/admin/users/search/";
                myOpener.document.forms[0].submit();
            }

            window.onbeforeunload = function()
            {
                if(needrefresh)
                    imrloadusr();
            };

        </script>
    </head>
    <body>
        <iframe style="z-index:1; visibility:visible; position:absolute; top:0px; left:0px; width:543px; height:300px;border:0px" scrolling="yes" id="idHFrameIW" src="import.php"  name="HFrameIW"></iframe>
    </body>
</html>
