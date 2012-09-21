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
use Alchemy\Phrasea\Core\Configuration;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

require_once __DIR__ . "/../../lib/bootstrap.php";
phrasea::headers(200, true);
$app = new Application();
$appbox = $app['phraseanet.appbox'];

$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "piv"
    , "id"
    , "src"
    , "rpl"
    , "field"
    , "dlg"
);

if ($parm["dlg"]) {
    $opener = "window.dialogArguments.win";
} else {
    $opener = "opener";
}
?>
<html lang="<?php echo $app['locale.I18n']; ?>">
    <body>
<?php
if ($parm["bid"] !== null) {
    try {
        $connbas = connection::getPDOConnection($app, $parm['bid']);

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
        }
        $sql = "SELECT record_id, BINARY xml AS xml FROM tmprecord WHERE $sql";
        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);
        $rsbas2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $nrectot = $stmt->rowCount();
        $stmt->closeCursor();

        $nrecdone = $nrecchanged = $nspot = 0;
        foreach ($rsbas2 as $rowbas2) {
            $nrecdone ++;
            printf("<script type=\"text/javascript\">parent.pbar($nrecdone, $nrectot);</script>\n");
            flush();

            set_time_limit(30);

            $xml = $rowbas2["xml"];
            $spots = array();
            foreach ($parm["field"] as $field) {
                $ibyte_min = $ichar_min = 0;
                while (true) {
                    if (($ibyte_min = strpos($xml, "<$field>", $ibyte_min)) === false)
                        break;
                    $ibyte_min += strlen("<$field>");
                    if (($ibyte_max = strpos($xml, "</$field>", $ibyte_min)) === false)
                        break;

                    $ichar_min = mb_strpos($xml, "<$field>", $ichar_min, "UTF-8") + mb_strlen("<$field>");
                    $ichar_max = mb_strpos($xml, "</$field>", $ichar_min, "UTF-8"); // + mb_strlen("</$field>");

                    $txml = substr($xml, $ibyte_min, $ibyte_max - $ibyte_min);

                    $xml_noacc_tchar = array();   // buffer circulaire taille+2 (car prec. et car suiv. pour trouver uniquement les mots entiers)
                    $xml_noacc_tchar[0] = array(">", ">", 1); // car precedent
                    for ($i = 0; $i < $src_noacc_len + 1; $i ++ ) {
                        $c = mb_substr($txml, 0, 1, "UTF-8");
                        $xml_noacc_tchar[$i + 1] = array($c, $unicode->remove_indexer_chars($c), $l = strlen($c));
                        $txml = substr($txml, $l);
                    }

                    for ($ib = $ibyte_min, $ic = $ichar_min; $ic <= $ichar_max - $src_noacc_len; $ic ++ ) {

                        if (isdelim($xml_noacc_tchar[0][0]) && isdelim($xml_noacc_tchar[$src_noacc_len + 1][0])) {
                            for ($i = 0; $i < $src_noacc_len; $i ++ ) {
                                if ($xml_noacc_tchar[$i + 1][1] !== $src_noacc_tchar[$i])
                                    break;
                            }

                            if ($i == $src_noacc_len) {
                                for ($l = 0, $i = 1; $i < $src_noacc_len + 1; $i ++ )
                                    $l += $xml_noacc_tchar[$i][2];

                                if (count($spots) == 0) {
                                    $nrecchanged ++;
                                }
                                $nspot ++;

                                $spots[$ib] = array("p"                => $ib, "l"                => $l);
                            }
                        }
                        $lost = array_shift($xml_noacc_tchar);
                        $c = mb_substr($txml, 0, 1, "UTF-8");
                        $xml_noacc_tchar[] = array($c, $unicode->remove_indexer_chars($c), $l = strlen($c));
                        // $txml = mb_substr($txml, 1, 9999, "UTF-8");
                        $txml = substr($txml, $l);
                        $ib += $lost[2];

                        $ibyte_min = $ibyte_max + strlen("</$field>");
                        $ichar_min = $ichar_max + mb_strlen("</$field>");
                    }
                }
            }
            if (count($spots) > 0) {
                ksort($spots);
                $dp = 0;
                $ddp = (strlen($parm["rpl"]) - strlen($parm["src"]));

                foreach ($spots as $spot) {
                    $xml = substr($xml, 0, $dp + $spot["p"]) . $parm["rpl"] . substr($xml, $dp + $spot["p"] + $spot["l"]);
                    $dp += $ddp; // strlen("<em></em>");
                }
                print($xml);
                print("<br/>\n");

                $sql = "UPDATE tmprecord SET xml = :xml";
                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':xml' => $xml));
                $stmt->closeCursor();
            }
        }
        printf("found %d times in %d records<br/>\n", $nspot, $nrecdone);
        printf("<script type=\"text/javascript\">parent.pdone($nrecdone, $nrectot, $nrecchanged, $nspot);</script>\n");
    } catch (Exception $e) {

    }
}

function isdelim($utf8char)
{
    $unicode = new unicode();

    return in_array($utf8char, $unicode->get_indexer_bad_chars());
}
?>
    </body>
</html>
