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
phrasea::headers(200, true);

$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();
$registry = $appbox->get_registry();
require($registry->get('GV_RootPath') . "www/thesaurus2/xmlhttp.php");

$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "piv"  // pivot
    , "pid"
    , "t"
    , "sylng" // lng nouvo sy
    , "typ"  // "TS" ou "SY"
    , "dlg"
);
?>
<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <title><?php echo $parm["typ"] == "TS" ? p4string::MakeString(_('thesaurus:: Nouveau terme specifique')) : p4string::MakeString(_('thesaurus:: Nouveau synonyme')) ?></title>

        <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />

        <script type="text/javascript" src="./xmlhttp.js"></script>
        <script type="text/javascript">
        </script>
    </head>
    <body onload="loaded();" class="dialog" style="text-align:center">
        <?php
        if ($parm["dlg"]) {
            $opener = "window.dialogArguments.win";
        } else {
            $opener = "opener";
        }


// print($parm["t"]);

        list($term, $context) = splitTermAndContext($parm["t"]);

        $url = "thesaurus2/xmlhttp/searchcandidate.x.php";
        $url .= "?bid=" . $parm["bid"];
        $url .= "&pid=" . $parm["pid"];
        $url .= "&piv=" . $parm["piv"];
        $url .= "&t=" . urlencode($term);
// if($context != "")
        $url .= "&k=" . urlencode($context);
        $dom = xmlhttp($url);


        $zterm = p4string::MakeString(sprintf(_('thesaurus:: le terme %s'), "<b>" . $term . "</b> "));
        if ($context != "")
            $zterm .= p4string::MakeString(sprintf(_('thesaurus:: avec contexte %s'), "<b>" . $context . "</b>"));
        else
            $zterm .= p4string::MakeString(_('thesaurus:: sans contexte'));

// print($dom->saveXML());

        $xpath = new DOMXPath($dom);

        $candidates = $xpath->query("/result/candidates_list/ct");

// on verifie si au moins un champ candidat est acceptable
        $nb_candidates_ok = $nb_candidates_bad = 0;
        $flist_ok = $flist_bad = "";
        for ($i = 0; $i < $candidates->length; $i ++ ) {
            if ($candidates->item($i)->getAttribute("sourceok") == "1") { // && $candidates->item($i)->getAttribute("cid"))
                $flist_ok .= ( $flist_ok ? ", " : "") . $candidates->item($i)->getAttribute("field");
                $nb_candidates_ok ++;
            } else {
                $flist_bad .= ( $flist_bad ? ", " : "") . $candidates->item($i)->getAttribute("field");
                $nb_candidates_bad ++;
            }
        }
        if ($nb_candidates_ok > 0) {
            // au moins un champ est acceptable : on presente des radio
            if ($nb_candidates_ok == 1)
                $t = p4string::MakeString(_('thesaurus:: est deja candidat en provenance du champ acceptable : '));
            else
                $t = p4string::MakeString(_('thesaurus:: est deja candidat en provenance des champs acceptables : '));
            ?>
            <br/>
            <br/>
            <?php echo $zterm ?>
            <br/>
            <br/>
            <?php echo $t ?>
            <br/>
        <center>
            <form onsubmit="return(false);">
                <input type="hidden" name="bid" value="<?php echo $parm["bid"] ?>">
                <input type="hidden" name="pid" value="<?php echo $parm["pid"] ?>">
            <?php
            print("\t\t\t<div class='x3Dbox' style='margin:15px; height:100px; overflow:auto;'>\n");
//  if($nb_candidates_bad)
//    printf("\t\t\t\t<span style=\"color:#606060\">%s</span><br/>\n", $flist_bad);
//  if($nb_candidates_ok)
//    printf("\t\t\t\t<span style=\"color:#000000\">%s</span><br/>\n", $flist_ok);
            // $ck = "checked";
            for ($i = 0; $i < $candidates->length; $i ++ ) {
                if ($candidates->item($i)->getAttribute("sourceok") == "1") {
//      printf("\t\t\t<input type=\"hidden\" name=\"cid\" value=\"%s\">\n", $candidates->item($i)->getAttribute("id") );
                    printf("\t\t<input type=\"radio\" name=\"cid\" value=\"%s\" onclick=\"return(clkCid());\">%s<br/>\n"
                        , $candidates->item($i)->getAttribute("id")
                        , $candidates->item($i)->getAttribute("field"));
                    // $ck = "";
                } else {
//      printf("\t\t<input type=\"radio\" disabled name=\"cid\" value=\"%s\" onclick=\"return(clkCid());\"><span style=\"color:#606060\">%s</span><br/>\n"
//                                      , $candidates->item($i)->getAttribute("id")
//                                      , $candidates->item($i)->getAttribute("field") );
                }
            }
            print("\t\t\t</div>\n");

            if ($nb_candidates_ok > 1)
                print(p4string::MakeString(_('thesaurus:: selectionner la provenance a accepter')) . "<br/>\n");
            ?>
                <br/>
                <input type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler')) ?>" onclick="clkBut('cancel');" style="width:100px;">
                &nbsp;&nbsp;&nbsp;
                <input type="button" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::valider')) ?>" onclick="clkBut('submit');" style="width:100px;">
            </form>
        </center>
                <?php
            }
            else {
                if ($nb_candidates_bad > 0) {
                    // present dans les candidats, mais aucun champ acceptable : on informe
                    if ($nb_candidates_bad == 1)
                        $t = p4string::MakeString(_('thesaurus:: est candidat en provenance des champs mais ne peut etre accepte a cet emplacement du thesaurus'));
                    else
                        $t = p4string::MakeString(_('thesaurus:: est candidat en provenance des champs mais ne peut etre accepte a cet emplacement du thesaurus'));
                }
                else {
                    // pas present dans les candidats
                    $t = p4string::MakeString(_('thesaurus:: n\'est pas present dans les candidats')) . "\n";
                }
                ?>
        <br/>
        <h3><?php echo p4string::MakeString(_('thesaurus:: attention :')) ?></h3>
        <br/>
        <br/>
        <?php echo $zterm ?>
        <br/>
        <br/>
        <?php echo $t ?>
        <br/>
        <form>
            <center>
                <div class='x3Dbox' style='margin:15px; height:90px; overflow:auto;'>
                    <input type="radio" name="reindex" value="0" id="rad0" checked><label for="rad0"><?php echo p4string::MakeString(_('thesaurus:: Ajouter le terme dans reindexer')) /* Ce terme n'est pas present dans la base, l'ajouter au thesaurus sans reindexer */ ?></label><br/>
                    <br/>
                    <input type="radio" name="reindex" value="1" id="rad1"><label for="rad1"><?php echo p4string::MakeString(_('thesaurus:: ajouter le terme et reindexer')) /* Ce terme est peut-etre present dans la base, marquer tous les documents comme 'e reindexer' */ ?></label><br/>
                </div>
            </center>
            <input type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler')) ?>" onclick="clkBut('cancel');" style="width:100px;">
            &nbsp;&nbsp;&nbsp;
            <input type="button" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::valider')) ?>" onclick="clkBut('submit');" style="width:100px;">
        </form>
    <?php
}
?>
</body>

<script type="text/javascript">
<?php
if ($nb_candidates_ok > 0) {
    ?>
    function clkCid()
    {
        cids = document.getElementsByName("cid");
        f = false;
        for(i=0; i<cids.length && !f; i++)
        {
            if(cids[i].checked)
                f = true;
        }
        document.getElementById("submit_button").disabled = !f;

        return(true);
    }
    function clkBut(button)
    {
        switch(button)
        {
            case "submit":
                url = "xmlhttp/acceptcandidates.x.php";
                parms  = "bid=<?php echo $parm["bid"] ?>";
                parms += "&pid=<?php echo $parm["pid"] ?>";
                parms += "&typ=<?php echo $parm["typ"] ?>";
                for(i=0; i<(n=document.getElementsByName("cid")).length; i++)
                {
                    if(n[i].checked)
                        parms += "&cid[]=" + encodeURIComponent(n[i].value);
                }

                //        if(!confirm(url+"?"+parms))
                //          return;

                ret = loadXMLDoc(url, parms, true);
                //alert(ret);
                //return;
                refresh = ret.getElementsByTagName("refresh");
                for(i=0; i<refresh.length; i++)
                {
                    switch(refresh.item(i).getAttribute("type"))
                    {
                        case "CT":
    <?php echo $opener ?>.reloadCtermsBranch(refresh.item(i).getAttribute("id"));
                            break;
                        case "TH":
    <?php echo $opener ?>.reloadThesaurusBranch(refresh.item(i).getAttribute("id"));
                            break;
                        }
                    }
                    self.close();
                    break;
                case "cancel":
                    self.close();
                    break;
                }
            }
    <?php
} else {
    ?>
            function clkCid()
            {}
            function clkBut(button)
            {
                switch(button)
                {
                    case "submit":
    <?php if ($parm["typ"] == "TS") {
        ?>
                                    url = "xmlhttp/newts.x.php";
    <?php
    } else {
        ?>
                                    url = "xmlhttp/newsy.x.php";
    <?php } ?>
                        parms  = "bid=<?php echo $parm["bid"] ?>";
                        parms += "&piv=<?php echo $parm["piv"] ?>";
                        parms += "&pid=<?php echo $parm["pid"] ?>";
                        parms += "&t=<?php echo urlencode($term) ?>";
    <?php if ($context != "") {
        ?>
                                    parms += "&k=<?php echo urlencode($context) ?>";
    <?php } ?>
                        parms += "&sylng=<?php echo $parm["sylng"] ?>";
                        // alert(url + "?" + parms);
                        for(i=0; i<(n=document.getElementsByName("reindex")).length; i++)
                        {
                            if(n[i].checked)
                            {
                                parms += "&reindex=" + encodeURIComponent(n[i].value);
                                break;
                            }
                        }
                        // alert(url + "?" + parms);
                        ret = loadXMLDoc(url, parms, true);
                        refresh = ret.getElementsByTagName("refresh");
                        for(i=0; i<refresh.length; i++)
                        {
                            // alert("type : " + refresh.item(i).getAttribute("type"));
                            // alert("id   : " + refresh.item(i).getAttribute("id"));

                            switch(refresh.item(i).getAttribute("type"))
                            {
                                case "CT":
    <?php echo $opener ?>.reloadCtermsBranch(refresh.item(i).getAttribute("id"));
                                    break;
                                case "TH":
    <?php echo $opener ?>.reloadThesaurusBranch(refresh.item(i).getAttribute("id"));
                                    break;
                                }
                            }
                            self.close();
                            break;
                        case "cancel":
                            self.close();
                            break;
                        }
                    }
    <?php
}
?>
                function loaded()
                {
                    clkCid();
                    self.focus();
                }
</script>
</html>
<?php

function splitTermAndContext($word)
{
    $term = trim($word);
    $context = "";
    if (($po = strpos($term, "(")) !== false) {
        if (($pc = strpos($term, ")", $po)) !== false) {
            $context = trim(substr($term, $po + 1, $pc - $po - 1));
            $term = trim(substr($term, 0, $po));
        }
    }

    return(array($term, $context));
}
?>
