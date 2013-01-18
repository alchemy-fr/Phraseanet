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

require_once __DIR__ . "/../../vendor/autoload.php";
$app = new Application();
phrasea::headers(200, true);

$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "piv"
    , "id"
    , "dlg"
);

if ($parm["dlg"]) {
    $opener = "window.dialogArguments.win";
} else {
    $opener = "opener";
}
?>
<html lang="<?php echo $app['locale.I18n']; ?>">
    <head>
        <base target="_self">
        <title><?php echo p4string::MakeString(_('thesaurus:: Importer')) ?></title>

        <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />
        <script type="text/javascript">
            function clkBut(button)
            {
                switch(button)
                {
                    case "submit":
                        document.forms[0].target='IFRIM';
                        document.forms[0].submit();
                        break;
                    case "cancel":
                        self.returnValue = null;
                        self.close();
                        break;
                }
            }
            function loaded()
            {
            }
            function importDone(err)
            {
                if(!err)
                {
<?php echo $opener ?>.reload();
                self.close();
            }
            else
            {
                alert(err);
            }
        }
        </script>
    </head>
    <body onload="loaded();" class="dialog">
        <br/>
        <form onsubmit="clkBut('submit');return(false);" action="import.php" enctype="multipart/form-data" method="post">
            <input type="hidden" name="bid" value="<?php echo $parm["bid"] ?>" >
            <input type="hidden" name="piv" value="<?php echo $parm["piv"] ?>" >
            <input type="hidden" name="id" value="<?php echo $parm["id"] ?>" >
            <input type="hidden" name="dlg" value="<?php echo $parm["dlg"] ?>" >
            <div>
              <!--<div style="float:left"><?php echo p4string::MakeString(_('thesaurus:: coller ici la liste des termes a importer')); /* Coller ici la liste des termes e importer : */ ?></div>-->
                <div style="float:right"><?php echo p4string::MakeString(_('thesaurus:: langue par default')) . "&nbsp;<img src='/skins/icons/flag_18.gif' />" . '&nbsp;' . $parm['piv']; ?></div>
            </div>
            <br/>
            <!--<textarea name="t" style="width:550px; height:200px" value=""></textarea>
              <br/>-->
            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo(16 * 1024 * 1024) ?>" />
            <!-- OU envoyer le fichier :-->
<?php echo _('Fichier ASCII tabule') ?>
            <input type="file" name="fil" />&nbsp;(max 16Mo)
            <br/>

            <div style="text-align:center">
                <!--
                    <div style="text-align:left; position:relative; top:0px; left:0px; display:inline; background-color:#ff0000; white-space:nowrap; xmargin-left:auto; xmargin-right:auto">
                        <p style="white-space:nowrap; width:auto">
                          <input type="checkbox" name="dlk" checked="1">Supprimer les liens des champs (tbranch)
                        </p>
                        <p style="white-space:nowrap; width:auto">
                          <input type="checkbox" name="rdx" checked="1">Reindexer la base apres l'import
                        </p>
                    </div>
                -->
                <table>
                    <tr>
                        <td style="text-align:left"><input type="checkbox" disabled="disabled" name="dlk" checked="checked"><?php echo p4string::MakeString(_('thesaurus:: supprimer les liens des champs tbranch')); /* Supprimer les liens des champs (tbranch) */ ?></td>
                    </tr>
                    <tr>
                        <td style="text-align:left"><input type="checkbox" disabled="disabled" name="rdx"><?php echo p4string::MakeString(_('thesaurus:: reindexer la base apres l\'import')); /* Reindexer la base apres l'import */ ?></td>
                    </tr>
                </table>
                <br/>
                <input type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler')) ?>" onclick="clkBut('cancel');" style="width:100px;">
                &nbsp;&nbsp;&nbsp;
                <input type="button" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::valider')) ?>" onclick="clkBut('submit');" style="width:100px;">
            </div>
        </form>
        <iframe style="display:none; height:50px;" name="IFRIM"></iframe>
    </body>
</html>
