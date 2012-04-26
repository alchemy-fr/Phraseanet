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
 * @package
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
    "bid"
    , "piv"
    , "id"
    , "typ"
    , "dlg"
    , 'obr' // liste des branches ouvertes
);

if ($parm["dlg"]) {
    $opener = "window.dialogArguments.win";
} else {
    $opener = "opener";
}
?>
<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <title><?php echo p4string::MakeString(_('thesaurus:: export en topics')) ?></title>

        <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />
        <script type="text/javascript">
            var format = '???';
            function clkBut(button)
            {
                switch(button)
                {
                    case "submit":
                        document.forms[0].target = (format == 'tofiles' ? "_self" : "EXPORT2");
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
                //      document.forms[0].t.focus();
                chgFormat();
            }
            function ckis()
            {
                document.getElementById("submit_button").disabled = document.forms[0].t.value=="";
            }
            function enable_inputs(o, stat)
            {
                if(o.nodeType==1)  // element
                {
                    if(o.nodeName=='INPUT')
                    {
                        if(stat)
                            o.removeAttribute('disabled');
                        else
                            o.setAttribute('disabled', true);
                    }
                    for(var oo=o.firstChild; oo; oo=oo.nextSibling)
                        enable_inputs(oo, stat)
                }
            }
            function chgFormat()
            {
                var i, f;
                for(i=0; i<document.forms[0].ofm.length; i++)
                {
                    f = document.forms[0].ofm[i].value;
                    if(document.forms[0].ofm[i].checked)
                    {
                        // enable_inputs(document.getElementById('subform_'+f), true);
                        format = f;
                    }
                    else
                    {
                        // enable_inputs(document.getElementById('subform_'+f), false);
                    }
                }
            }
        </script>
    </head>
    <body onload="loaded();" class="dialog">
    <center>
        <form onsubmit="clkBut('submit');return(false);" action="export_topics.php">
            <input type="hidden" name="bid" value="<?php echo $parm["bid"] ?>" >
            <input type="hidden" name="piv" value="<?php echo $parm["piv"] ?>" >
            <input type="hidden" name="id" value="<?php echo $parm["id"] ?>" >
            <input type="hidden" name="typ" value="<?php echo $parm["typ"] ?>" >
            <input type="hidden" name="dlg" value="<?php echo $parm["dlg"] ?>" >
            <input type="hidden" name="obr" value="<?php echo $parm["obr"] ?>" >

            <div style="padding:10px;">
                <div class="x3Dbox">
                    <span class="title"><?php echo p4string::MakeString(_('thesaurus:: exporter')) /* export */ ?></span>
                    <div style="white-space:nowrap">
                        <input type='radio' name='ofm' checked value='tofiles' onclick="chgFormat();">
<?php echo p4string::MakeString(_('thesaurus:: exporter vers topics pour toutes les langues')) /* vers les topics, pour toutes les langues */ ?>
                    </div>
                    <!--
                        <div id='subform_tofiles' style="margin-left:10px;">
                        </div>
                    -->
                    <div style="white-space:nowrap">
                        <input type='radio' name='ofm' value='toscreen' onclick="chgFormat();">
<?php echo p4string::MakeString(_('thesaurus:: exporter a l\'ecran pour la langue _langue_')) . $parm['piv']; ?>
                    </div>
                </div>

                <br/>

                <div class="x3Dbox">
                    <span class="title"><?php echo p4string::MakeString(_('phraseanet:: tri')) /* tri */ ?></span>
                    <div style="white-space:nowrap">
                        <input type='checkbox' name='srt' checked onclick="chgFormat();">
<?php echo p4string::MakeString(_('phraseanet:: tri par date')) /* tri */ ?>
                    </div>
                </div>

                <br/>

                <div class="x3Dbox">
                    <span class="title"><?php echo p4string::MakeString(_('thesaurus:: recherche')); ?></span>
                    <div style="white-space:nowrap">
                        <input type='radio' name='sth' value="1" checked onclick="chgFormat();">
<?php echo p4string::MakeString(_('thesaurus:: recherche thesaurus *:"query"')); ?>
                    </div>
                    <div style="white-space:nowrap">
                        <input type='radio' name='sth' value="0" onclick="chgFormat();">
                        <?php echo p4string::MakeString(_('thesaurus:: recherche fulltext')); /* recherche thesaurus */ ?>
                    </div>
                    <div style="white-space:nowrap">
                        <input type='checkbox' name='sand' onclick="chgFormat();">
                        <?php echo p4string::MakeString(_('thesaurus:: question complete (avec operateurs)')); /* full query, with 'and's */ ?>
                    </div>
                </div>

                <br/>

                <div class="x3Dbox">
                    <span class="title"><?php echo p4string::MakeString(_('thesaurus:: presentation')) ?></span>
                    <div style="white-space:nowrap">
                        <input type='radio' name='obrf' value="from_itf_closable" checked onclick="chgFormat();">
<?php echo p4string::MakeString(_('thesaurus:: presentation : branches refermables')) ?>
                    </div>
                    <div style="white-space:nowrap">
                        <input type='radio' name='obrf' value="from_itf_static" onclick="chgFormat();">
                        <?php echo p4string::MakeString(_('thesaurus:: presentation : branche ouvertes')) ?>
                    </div>
                    <div style="white-space:nowrap">
                        <input type='radio' name='obrf' value="all_opened_closable" onclick="chgFormat();">
                        <?php echo p4string::MakeString(_('thesaurus:: tout deployer - refermable')) /* Tout dployer (refermable) */ ?>
                    </div>
                    <div style="white-space:nowrap">
                        <input type='radio' name='obrf' value="all_opened_static" onclick="chgFormat();">
                        <?php echo p4string::MakeString(_('thesaurus:: tout deployer - statique')) /* Tout dployer (statique) */ ?>
                    </div>
                    <div style="white-space:nowrap">
                        <input type='radio' name='obrf' value="all_closed" onclick="chgFormat();">
                        <?php echo p4string::MakeString(_('thesaurus:: tout fermer')) /* Tout fermer */ ?>
                    </div>
                </div>
            </div>
            <input type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler')) ?>" onclick="clkBut('cancel');" style="width:100px;">
            &nbsp;&nbsp;&nbsp;
            <input type="button" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::valider')) ?>" onclick="clkBut('submit');" style="width:100px;">
        </form>
    </center>
</body>
</html>
