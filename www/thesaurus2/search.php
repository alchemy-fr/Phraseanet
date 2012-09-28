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
phrasea::headers(200, true);
$app = new Application();

$request = http_request::getInstance();
$parm = $request->get_parms(
    "dlg"
    , "piv"
);

if ($parm["dlg"]) {
    $opener = "window.dialogArguments.win";
} else {
    $opener = "opener";
}
?>
<html lang="<?php echo $app['locale.I18n']; ?>">
    <head>
        <title>Chercher</title>

        <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />
        <script type="text/javascript">
            function clkBut(button)
            {
                switch(button)
                {
                    case "submit":
                        m = null;
                        for(i=0; !m && document.forms[0].m[i]; i++)
                            m = document.forms[0].m[i].checked ? document.forms[0].m[i].value : null;
                        self.returnValue = { t:document.forms[0].t.value, method:m };
                        self.close();
                        break;
                    case "cancel":
                        self.returnValue = null;
                        self.close();
                        break;
                }
            }
            function loaded()
            {
                document.forms[0].t.focus();
            }
            function ckis()
            {
                document.getElementById("submit_button").disabled = document.forms[0].t.value=="";
            }
        </script>
    </head>
    <body onload="loaded();" class="dialog">
    <center>
        <br/>
        <br/>
        <form onsubmit="clkBut('submit');return(false);">
            <table>
                <tr>
                    <td><?php echo p4string::MakeString(_('thesaurus:: le terme')) ?></td>
                    <td><input type="radio" name="m" value="equal"><?php echo p4string::MakeString(_('thesaurus:: est egal a ')) /* est egal e */ ?></td>
                </tr>
                <tr>
                    <td />
                    <td><input type="radio" checked name="m" value="begins"><?php echo p4string::MakeString(_('thesaurus:: commence par')) /* commence par */ ?></td>
                </tr>
                <tr>
                    <td />
                    <td><input type="radio" name="m" value="contains"><?php echo p4string::MakeString(_('thesaurus:: contient')) /* contient */ ?></td>
                </tr>
                <!--
                <tr>
                  <td />
                  <td><input type="radio" name="m" value="ends"><?php echo p4string::MakeString(_('thesaurus:: fini par')) /* finit par */ ?></td>
                  </tr>
                -->
            </table>
            <br/>
            <input type="text" name="t" value="" style="width:200px" onkeyup="ckis();return(true);">
            <br/>
            <br/>
            <br/>
            <input type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler')) ?>" onclick="clkBut('cancel');" style="width:80px;">
            &nbsp;&nbsp;&nbsp;
            <input type="button" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::chercher')) ?>" onclick="clkBut('submit');" disabled style="width:80px;">
        </form>
    </center>
</body>
</html>
