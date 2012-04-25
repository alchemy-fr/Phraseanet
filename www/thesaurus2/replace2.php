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
    , "piv"
    , "pid"  // id du pere (te)
    , "id"  // id du synonyme (sy)
    , "src"
    , "rpl"
    , "rplrec"
    , "field"
    , "dlg"
);

if ($parm["dlg"]) {
    $opener = "window.dialogArguments.win";
} else {
    $opener = "opener";
}

$url3 = "./replace3.php";
$url3 .= "?bid=" . urlencode($parm["bid"]);
$url3 .= "&piv=" . urlencode($parm["piv"]);
$url3 .= "&id=" . urlencode($parm["id"]);
$url3 .= "&src=" . urlencode($parm["src"]);
$url3 .= "&rplrec=" . urlencode($parm["rplrec"]);
$url3 .= "&rpl=" . urlencode($parm["rpl"]);
$lstfld = "";
if ($parm["rplrec"] && is_array($parm["field"])) {
    foreach ($parm["field"] as $f) {
        $url3 .= "&field[]=" . urlencode($f);
        $lstfld .= ( $lstfld ? ", " : "") . "<b>" . $f . "</b>";
    }
}
$url3 .= "&dlg=" . urlencode($parm["dlg"] ? 1 : 0);
?>
<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <title>Corriger...</title>

        <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />
        <style type="text/css">
            #baranchor DIV
            {
                POSITION:absolute;
                LEFT:0px;
                TOP:0px;
                BORDER:#0000FF 1px solid;
                FONT-WEIGHT:bold;
                FONT-SIZE: 13px;
                OVERFLOW:hidden;

            }
            #barBg
            {
                BACKGROUND-COLOR:#ffffff;
                COLOR:#0000FF;
            }
            #barMv
            {
                CLIP:rect(0px 0px auto 0px);
                BACKGROUND-POSITION: left top;
                BACKGROUND-IMAGE: url(./images/pbar.gif);
                COLOR:#FFFFFF;
            }
        </style>

        <script type="text/javascript" src="./xmlhttp.js"></script>
        <script type="text/javascript">
            function loaded()
            {
                window.name="REPLACE";
                self.focus();
            }
            function doContinue()
            {
                baranchor.style.visibility="visible";
                msg.innerText = "";
                cmd = "document.getElementById(\"REPL3\").src = \"<?php echo $url3 ?>\"";
                self.setTimeout(cmd, 100);
            }
            function pbar( newtraite , newnbtotal )
            {
                if(newtraite < 0)
                    newtraite = 0;
                else
                    if(newtraite > newnbtotal)
                        newtraite = newnbtotal;

                percent = Math.round( (newtraite/newnbtotal) *100 );
                widthIE = barBg.style.pixelWidth;
                clipright = Math.floor(widthIE * (newtraite/newnbtotal) );

                document.getElementById("barMv").style.clip="rect(0px "+(clipright+1)+"px auto 0px)";
                document.getElementById("barMv").innerHTML = document.getElementById("barBg").innerHTML = percent + "%";
                document.getElementById("barCptr").innerHTML = "( " + newtraite + " / " + newnbtotal + ")";
            }
            function pdone(nrecdone, nrectot, nrecchanged, nspot)
            {
                msg.innerText = nspot + "<?php echo utf8_encode(" remplacements effectues dans ") ?>" + nrecchanged + " documents.";
            }
        </script>
    </head>
<?php
$out = "";
try {
    if ($parm["bid"] === null)
        throw new Exception("bid is null");

    $connbas = connection::getPDOConnection($parm['bid']);

    list($term, $context) = splitTermAndContext($parm["rpl"]);
    $url = "thesaurus2/xmlhttp/searchcandidate.x.php";
    $url .= "?bid=" . $parm["bid"];
    $url .= "&pid=" . $parm["pid"];
    $url .= "&t=" . urlencode($term);
    // if($context != "")
    $url .= "&k=" . urlencode($context);
    $dom = xmlhttp($url);

    print("<!-- $url  -->\n");
    // print($dom->saveXML());

    $xpath = new DOMXPath($dom);

    $candidates = $xpath->query("/result/candidates_list/ct");
    if ($candidates->length > 0) {
        // le terme saisi existait dans les candidats, on peut choisir qui accepter
        ?>
            <form onsubmit="return(false);">
                <input type="hidden" name="bid" value="<?php echo $parm["bid"] ?>">
                <input type="hidden" name="pid" value="<?php echo $parm["pid"] ?>">
                <center>
            <?php
            $t = "<br/>le terme <b>" . $term . "</b>";
            if ($context != "")
                $t .= " (avec contexte <b>" . $context . "</b>)";
            $t .= utf8_encode(" est deje candidat en provenance");
            $t .= ( $candidates->length == 1) ? " du champ :" : " des champs suivants :";
            $t .= "<br/><br/>\n";

            print($t);

            $candidates_ok = 0;
            for ($i = 0; $i < $candidates->length; $i ++ ) {
                if ($candidates->item($i)->getAttribute("sourceok") == "1")
                    $candidates_ok ++;
            }

            print("<div class='x3Dbox' style='width:70%; height:120px; overflow:auto'>\n");
            for ($i = 0; $i < $candidates->length; $i ++ ) {
                if ($candidates->item($i)->getAttribute("sourceok") == "1") {
                    printf("\t\t<input type=\"radio\" name=\"cid\" value=\"%s\" onclick=\"return(clkCid());\">%s<br/>\n"
                        , $candidates->item($i)->getAttribute("id")
                        , $candidates->item($i)->getAttribute("field"));
                } else {
                    printf("\t\t<input type=\"radio\" disabled name=\"cid\" value=\"%s\" onclick=\"return(clkCid());\">%s<br/>\n"
                        , $candidates->item($i)->getAttribute("id")
                        , $candidates->item($i)->getAttribute("field"));
                }
            }
            print("</div><br/>\n");
            if ($candidates_ok > 1)
                print(utf8_encode("selectionnez la provenance e accepter.<br/>\n"));
        }

        $nrec = 0;
        if ($parm["rplrec"]) {   // remplacer egalement dans les record
            // table temporaire
            $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `tmprecord` (`xml` TEXT COLLATE utf8_general_ci) SELECT record_id, xml FROM record";
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            $unicode = new unicode();
            $src_noacc = $unicode->remove_indexer_chars($parm["src"]);
            $src_noacc_len = mb_strlen($src_noacc, "UTF-8");
            $src_noacc_tchar = array();
            for ($i = 0; $i < $src_noacc_len; $i ++ )
                $src_noacc_tchar[$i] = mb_substr($src_noacc, $i, 1, "UTF-8");

            $sql = "";
            $params = array();
            $n = 0;
            foreach ($parm["field"] as $field) {
                $params[':like' . $n] = "%<$field>%" . $src_noacc . "%</$field>%";
                $sql .= ( $sql == "" ? "" : " OR ") . "(xml LIKE :like" . $n . ")";
                $n ++;
            }

            $sql = "SELECT record_id, BINARY xml AS xml FROM tmprecord WHERE $sql";
            $stmt = $connbas->prepare($sql);
            $stmt->execute($params);
            $nrec = $stmt->rowCount();
            $stmt->closeCursor();

            $out .= "remplacement de <b>" . $parm["src"] . "</b> par <b>" . $parm["rpl"] . "</b> dans le champ " . $lstfld . "<br/>\n";
            $out .= "      <br/>\n";

            $out .= "      <DIV id=\"baranchor\" style=\"position:relative; width:400px; height:18px; visibility:hidden;\">\n";
            $out .= "        <div id=\"barBg\" align=\"center\" style=\"width:400px; height:18px; z-index:9\">0%</div>\n";
            $out .= "        <div id=\"barMv\" align=\"center\" style=\"width:400px; height:18px; z-index:10\">0%</div>\n";
            $out .= "      </DIV>\n";
            $out .= "      <div id=\"barCptr\">&nbsp;</div>\n";
            $out .= "      <br/>\n";

            if ($nrec >= 0) {
                $out .= "      <div id=\"msg\">" . utf8_encode("      $nrec documents concernes !") . "</div>\n";
                $out .= "      <br/>\n      <br/>\n";
                $out .= "      <input type=\"button\"  style=\"width:80px\" value=\"Annuler\" onclick=\"self.close();return(false);\">\n";
                $out .= "      &nbsp;&nbsp;&nbsp;\n";
                $out .= "      <input type=\"button\" style=\"width:80px\" value=\"Remplacer\" onclick=\"doContinue();return(false);\">\n";
                $onload = "loaded();";
            } else {
                $out .= "      <div id=\"msg\">" . utf8_encode("      $nrec records concernes !") . "</div>\n";
                $out .= "      <br/>\n      <br/>\n";
                $out .= "      <input type=\"button\" style=\"width:80px\" value=\"Annuler\" onclick=\"self.close();return(false);\">\n";
                $onload = "loaded();doContinue();";
            }
        } else {
            $onload = "loaded();";
        }
        ?>
                <body onload="<?php echo $onload ?>" class="dialog">
                <center>
                    <br/>
                    <form onsubmit="return(false);">
                <?php echo $out ?>
                    </form>
                    <br/>
                    <iframe src="about:blank" id="REPL3"></iframe>
                </center>
                </body>
                <?php
            } catch (Exception $err) {
                echo $err;
            }

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
            </html>
