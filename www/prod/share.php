<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

require_once __DIR__ . "/../../lib/bootstrap.php";
$app = new Application();
phrasea::headers();

$request = http_request::getInstance();
$parm = $request->get_parms("bas", "rec");

$user = $app['phraseanet.user'];

$right = false;
?>

<html lang="<?php echo $app['locale.I18n']; ?>">
    <head>
        <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,include/jslibs/jquery-ui-1.8.17/css/ui-lightness/jquery-ui-1.8.17.custom.css,skins/prod/<?php echo $user->getPrefs('css') ?>/prodcolor.css" />
        <script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.7.1.js"></script>
        <script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.17/js/jquery-ui-1.8.17.custom.min.js"></script>

        <script language="javascript">

            $(document).ready(function(){
                $('#tabs').tabs();
                $('input.ui-state-default').hover(
                function(){$(this).addClass('ui-state-hover')},
                function(){$(this).removeClass('ui-state-hover')}
            );

            });
        </script>
    </head>

    <body class="bodyprofile">
        <div id="tabs">
            <ul><li><a href="#share"><?php echo _('reponses:: partager'); ?></a></li></ul>

            <div id="share">
                <?php
                $sbas_id = phrasea::sbasFromBas($app, $parm['bas']);
                $record = new record_adapter($app, $sbas_id, $parm['rec']);
                $right = ($user->ACL()->has_right_on_sbas($sbas_id, 'bas_chupub')
                    && $user->ACL()->has_access_to_subdef($record, 'preview'));

                if ( ! $right)
                    exit('ERROR<br><input class="input-button" type="button" value="' . _('boutton::fermer') . '" onclick="parent.hideDwnl();" /> </body></html>');


                $sha256 = $record->get_sha256();
                $type = $record->get_type();

                $url = '';

                $url = $record->get_preview()->get_permalink()->get_url();

                $embed = '';

                if ($url != '') {
                    switch ($type) {
                        case 'video':
                            $embed = '<object width="100%" height="100%" type="application/x-shockwave-flash" data="' . $app['phraseanet.registry']->get('GV_ServerName') . 'include/jslibs/flowplayer/flowplayer-3.2.12.swf">' .
                                '<param value="true" name="allowfullscreen">' .
                                '<param value="always" name="allowscriptaccess">' .
                                '<param value="high" name="quality">' .
                                '<param value="false" name="cachebusting">' .
                                '<param value="#000000" name="bgcolor">' .
                                '<param value="config={&quot;clip&quot;:{&quot;url&quot;:&quot;' . $url . '&quot;},&quot;playlist&quot;:[{&quot;url&quot;:&quot;' . $url . '&quot;}]}" name="flashvars">' .
                                '</object>';
                            break;
                        case 'document':
                            $embed = '<object width="600" height="500" type="application/x-shockwave-flash" data="' . $app['phraseanet.registry']->get('GV_ServerName') . 'include/FlexPaper_flash/FlexPaperViewer.swf" style="visibility: visible; width: 600px; height: 500px; top: 0px;">' .
                                '<param name="menu" value="false">' .
                                '<param name="flashvars" value="SwfFile=' . urlencode($url) . '&amp;Scale=0.6&amp;ZoomTransition=easeOut&amp;ZoomTime=0.5&amp;ZoomInterval=0.1&amp;FitPageOnLoad=true&amp;FitWidthOnLoad=true&amp;PrintEnabled=false&amp;FullScreenAsMaxWindow=false&amp;localeChain=' . $app['locale'] . '">' .
                                '<param name="allowFullScreen" value="true">' .
                                '<param name="wmode" value="transparent">' .
                                '</object>';
                            break;
                        case 'audio':
                            $embed = '<object width="290" height="24" data="' . $app['phraseanet.registry']->get('GV_ServerName') . 'include/jslibs/audio-player/player.swf" type="application/x-shockwave-flash">' .
                                '<param value="' . $app['phraseanet.registry']->get('GV_ServerName') . 'include/jslibs/audio-player/player.swf" name="movie"/>' .
                                '<param value="playerID=1&amp;autostart=yes&amp;soundFile=' . urlencode($url) . '" name="FlashVars"/>' .
                                '<param value="high" name="quality"/>' .
                                '<param value="false" name="menu"/>' .
                                '</object>';
                            break;
                        case 'image':
                        default:
                            $embed = '<a href="' . $url . 'view/"><img src="' . $url . '" title="" /></a>';
                            break;
                    }
                }
                ?>
                <div class="boxCloser" onclick="parent.hideDwnl();"><?php echo _('boutton::fermer') ?></div>
                <div id="tweet">
                    <div style="margin-left:20px;padding:10px 0 5px;"><a href="http://www.twitter.com/home/?status=<?php echo $url . 'view/' ?>" target="_blank"><img src="/skins/icons/twitter.ico" title="share this on twitter" style="vertical-align:middle;padding:0 5px;"/> Send to Twitter</a></div>
                    <div style="margin-left:20px;padding:5px 0 10px;"><a href="http://www.facebook.com/sharer.php?u=<?php echo $url . 'view/' ?>" target="_blank"><img src="/skins/icons/facebook.ico" title="share on facebook" style="vertical-align:middle;padding:0 5px;"/> Send to Facebook</a></div>
                </div>
                <div id="embed" style="text-align:center;padding:10px 0;">
                    <div style="text-align:left;margin-left:20px;padding:10px 0;">URL : </div>
<?php
if ($url != '') {
    ?>
                        <input style="width:90%;" readonly="true" type="text"  value="<?php echo $url ?>view/" onfocus="this.focus();this.select();" onclick="this.focus();this.select();" />
                        <?php
                    } else {
                        ?>
                        <div><?php echo _('Aucune URL disponible'); ?></div>
                        <?php
                    }
                    ?>
                    <div style="text-align:left;margin-left:20px;padding:10px 0;">Embed :</div>
                    <?php
                    if ($embed != '') {
                        ?>
                        <textarea onfocus="this.focus();this.select();" onclick="this.focus();this.select();" style="width:90%;height:50px;" readonly="true" ><?php echo $embed ?></textarea>
                        <?php
                    } else {
                        ?>
                        <div><?php echo _('Aucun code disponible'); ?></div>
                        <?php
                    }
                    ?>
                </div>


                <div style="text-align:center;padding:20px 0;">
                    <input class="input-button" type="button" value="<?php echo _('boutton::fermer') ?>" onclick="parent.hideDwnl();" />
                </div>
            </div>
        </div>
    </body>
</html>
