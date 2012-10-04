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
    "bid"
    , "piv"
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
        <title>Relire les candidats</title>

        <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />

        <script type="text/javascript">
            function loaded()
            {
                window.name="RESCAN";
                self.focus();
                ckbut();
            }

            function clkBut(but)
            {
                switch(but)
                {
                    case 'cancel':
                        self.close();
                        break;
                    case 'submit':
                        self.document.forms[0].submit();
                        break;
                }
            }
            function ckbut()
            {
                if(document.forms[0].dct.checked || document.forms[0].drt.checked)
                    document.getElementById("submit_button").disabled = false;
                else
                    document.getElementById("submit_button").disabled = true;
            }
        </script>
    </head>
    <body onload="loaded();" class="dialog">
        <form onsubmit="return(false);" action="./rescan.php" method="post">
            <div style="padding:30px">

<?php
if ($parm["bid"] !== null) {
    $loaded = false;
    try {
        $databox = $app['phraseanet.appbox']->get_databox((int) $parm['bid']);
        $connbas = connection::getPDOConnection($app, $parm['bid']);

        $nrec = 0;
        $sql = "SELECT COUNT(*) AS nrec FROM record";
        $stmt = $connbas->prepare($sql);
        $stmt->execute();
        $rowbas = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($rowbas)
            $nrec = $rowbas["nrec"];

        $domct = $databox->get_dom_cterms();

        if ($domct) {
            $r = countCandidates($domct->documentElement);

            printf(utf8_encode("%s termes candidats, %s termes refuses<br/><br/>\n"), $r["nc"], $r["nr"]);
            ?>
                            <input type="hidden" name="bid" value="<?php echo $parm["bid"] ?>">
                            <input type="hidden" name="piv" value="<?php echo $parm["piv"] ?>">
                            <input type="hidden" name="dlg" value="<?php echo $parm["dlg"] ?>">
                            <input type="checkbox" name="dct" onchange="ckbut();"><?php echo utf8_encode("Supprimer les " . $r["nc"] . " candidats...") ?><br/>
                            <input type="checkbox" name="drt" onchange="ckbut();"><?php echo utf8_encode("Supprimer les " . $r["nr"] . " termes refuses...") ?><br/>
                            <br/>
                            <?php echo utf8_encode("...et placer les $nrec fiches en reindexation-thesaurus ?<br/>\n"); ?>
                            <br/>
                        </div>
                        <div style="position:absolute; left:0px; bottom:0px; width:100%; text-align:center">
                            <input type="button" style="width:80px;" id="cancel_button" value="Annuler" onclick="clkBut('cancel');">
                            &nbsp;&nbsp;
                            <input type="button" style="width:80px;" id="submit_button" value="Ok" onclick="clkBut('submit');">
                            <br/>
                            <br/>
                        </div>
                    </form>
            <?php
        }
    } catch (Exception $e) {

    }
}

function countCandidates(&$node)
{
    global $parm;
    $ret = array("nc" => 0, "nr" => 0);
    if ($node->nodeType == XML_ELEMENT_NODE && $node->nodeName == "sy" && strlen($id = $node->getAttribute("id")) > 1) {
        if (substr($id, 0, 1) == "C")
            $ret["nc"] ++;
        elseif (substr($id, 0, 1) == "R")
            $ret["nr"] ++;
    }
    for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
        $r = countCandidates($n);
        $ret["nc"] += $r["nc"];
        $ret["nr"] += $r["nr"];
    }

    return($ret);
}
?>
    </body>
    <script type="text/javascript">
    </script>
</html>
