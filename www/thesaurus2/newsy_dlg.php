<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
phrasea::headers(200, true);
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms(
    "piv"
    , "typ"  // type de dlg : "TS"=nouvo terme specifique ; "SY"=nouvo synonyme
);

$lng = Session_Handler::get_locale();

switch ($parm["typ"]) {
    case "TS":
        $tstr = array(p4string::MakeString(_('thesaurus:: Nouveau terme')), p4string::MakeString(_('thesaurus:: terme')));
        break;
    case "SY":
        $tstr = array(p4string::MakeString(_('thesaurus:: Nouveau synonyme')), p4string::MakeString(_('thesaurus:: synonyme')));
        break;
    default:
        $tstr = array("", "");
        break;
}
?>
<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <title><?php echo $tstr[0] ?></title>

        <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />

        <script type="text/javascript">
            self.returValue = null;
            function clkBut(button)
            {
                switch (button) {
                    case "submit":
                        t = document.forms[0].term.value;
                        k = document.forms[0].context.value;
                        if(k != "")
                            t += " ("+k+")";
                        self.returnValue = {"t":t, "lng":null };
                        for (i=0; i<(n=document.getElementsByName("lng")).length; i++) {
                            if (n[i].checked) {
                                self.returnValue.lng = n[i].value;
                                break;
                            }
                        }
                        //        self.setTimeout('self.close();', 3000);
                        self.close();
                        break;
                    case "cancel":
                        self.close();
                        break;
                    }
                }
        </script>
    </head>

    <body class="dialog" onload="self.document.forms[0].term.focus();">
        <br/>
        <form onsubmit="return(false);">
            <table cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="text-align:right; width:80px;"><?php echo $tstr[1] ?> :&nbsp;</td>
                    <td></td>
                    <td><input type="text" style="width:250px;" name="term"></td>
                </tr>
                <tr>
                    <td style="text-align:right"><?php echo p4string::MakeString(_('thesaurus:: contexte')) /* Contexte */ ?> :&nbsp;</td>
                    <td><b>(</b>&nbsp;</td>
                    <td><input type="text" style="width:250px;" name="context">&nbsp;<b>)</b></td>
                </tr>
                <tr>
                    <td valign="bottom" style="text-align:right"><?php echo p4string::MakeString(_('phraseanet:: language')) /* Langue */ ?> :&nbsp;</td>
                    <td></td>
                    <td valign="bottom">
<?php
$tlng = User_Adapter::avLanguages();
foreach ($tlng as $lng_code => $lng) {
    $ck = $lng_code == $parm["piv"] ? " checked" : "";
    ?>
                            <span style="display:inline-block">
                                <input type="radio" <?php echo $ck ?> name="lng" value="<?php echo $lng_code ?>" id="lng_<?php echo $lng_code ?>">
                                <label for="lng_<?php echo $lng_code ?>"><img src="/skins/lng/<?php echo $lng_code ?>_flag_18.gif" />(<?php echo $lng_code ?>)</label>
                            </span>
                            &nbsp;&nbsp;
    <?php
}
?>
                    </td>
                </tr>
            </table>
            <br/>
            <div style="position:absolute; left:0px; bottom:0px; width:100%; text-align:center">
                <input type="button" style="width:80px;" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler')) ?>" onclick="clkBut('cancel');" style="width:80px">
                &nbsp;&nbsp;
                <input type="button" style="width:80px;" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::valider')) ?>" onclick="clkBut('submit');" style="width:80px">
                <br/>
                <br/>
            </div>
        </form>
    </body>
</html>
