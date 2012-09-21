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
require_once __DIR__ . "/../../../lib/bootstrap.php";
$app = new Application();
$appbox = $app['phraseanet.appbox'];
$registry = $app['phraseanet.registry'];


$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "id"
    , "typ"  // "TH" (thesaurus) ou "CT" (cterms)
    , "piv"
    , "sortsy" // trier la liste des sy (="1") ou pas
    , "sel"  // selectionner ce synonyme
    , "nots" // ne pas lister les ts
    , "acf"  // si TH, verifier si on accepte les candidats en provenance de ce champ
    , "debug"
);

if ($parm["debug"]) {
    phrasea::headers(200, true, 'text/html', 'UTF-8', true);
} else {
    phrasea::headers(200, true, 'text/xml', 'UTF-8', false);
}

$ret = new DOMDocument("1.0", "UTF-8");
$ret->standalone = true;
$ret->preserveWhiteSpace = false;
$root = $ret->appendChild($ret->createElement("result"));
$root->appendChild($ret->createCDATASection(var_export($parm, true)));
$cfield = $root->appendChild($ret->createElement("cfield"));
$ts_list = $root->appendChild($ret->createElement("ts_list"));
$sy_list = $root->appendChild($ret->createElement("sy_list"));
if ($parm["bid"] !== null) {
    $loaded = false;
    try {
        $databox = $appbox->get_databox((int) $parm['bid']);
        $connbas = $databox->get_connection();

        if ($parm["typ"] == "CT") {
            $xqroot = "cterms";
            $dom = $databox->get_dom_cterms();
        } else {
            $xqroot = "thesaurus";
            $dom = $databox->get_dom_thesaurus();
        }

        $meta = $databox->get_meta_structure();

        if ($dom) {
            $xpath = new DOMXPath($dom);
            if ($parm["typ"] == "TH" && $parm["acf"]) {
                $cfield->setAttribute("field", $parm["acf"]);

                // on doit verifier si le terme demande est accessible e partir de ce champ acf
                if ($parm["acf"] == '*') {
                    // le champ "*" est la corbeille, il est toujours accepte
                    $cfield->setAttribute("acceptable", "1");
                } else {
                    if (($databox_field = $meta->get_element_by_name($parm["acf"])) instanceof databox_field) {
                        $tbranch = $databox_field->get_tbranch();
                        $q = "(" . $tbranch . ")/descendant-or-self::te[@id='" . $parm["id"] . "']";

                        if ($parm["debug"])
                            printf("tbranch-q = \" $q \" <br/>\n");

                        $nodes = $xpath->query($q);
                        $cfield->setAttribute("acceptable", ($nodes->length > 0) ? "1" : "0");
                    }
                }
                /*
                 */
            }

            if ($parm["id"] == "T") {
                $q = "/thesaurus";
            } elseif ($parm["id"] == "C") {
                $q = "/cterms";
            } else {
                $q = "/$xqroot//te[@id='" . $parm["id"] . "']";
            }
            if ($parm["debug"])
                print("q:" . $q . "<br/>\n");

            $nodes = $xpath->query($q);
            $root->setAttribute('found', '' . $nodes->length);
            if ($nodes->length > 0) {
                $nts = 0;
                $tts = array();
                // on dresse la liste des termes specifiques avec comme cle le synonyme dans la langue pivot
                for ($n = $nodes->item(0)->firstChild; $n; $n = $n->nextSibling) {
                    if ($n->nodeName == "te") {
                        $nts ++;
                        if ( ! $parm["nots"]) {
                            if ($parm["typ"] == "CT" && $parm["id"] == "C") {
                                $realksy = $allsy = $n->getAttribute("field");
                            } else {
                                $allsy = "";
                                $firstksy = null;
                                $ksy = $realksy = null;
                                // on liste les sy pour fabriquer la cle
                                for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
                                    if ($n2->nodeName == "sy") {
                                        $lng = $n2->getAttribute("lng");
                                        $t = $n2->getAttribute("v");
                                        $ksy = $n2->getAttribute("w");
                                        if ($k = $n2->getAttribute("k")) {
                                            //        $t .= " ($k)";
                                            //        $ksy .= " ($k)";
                                        }
                                        if ( ! $firstksy)
                                            $firstksy = $ksy;
                                        if ( ! $realksy && $parm["piv"] && $lng == $parm["piv"]) {
                                            $realksy = $ksy;
                                            // $allsy = "<b>" . $t . "</b>" . ($allsy ? " ; ":"") . $allsy;
                                            $allsy = $t . ($allsy ? " ; " : "") . $allsy;
                                        } else {
                                            $allsy .= ( $allsy ? " ; " : "") . $t;
                                        }
                                    }
                                }
                                if ( ! $realksy)
                                    $realksy = $firstksy;
                            }
                            if ($parm["sortsy"] && $parm["piv"]) {
                                for ($uniq = 0; $uniq < 9999; $uniq ++ ) {
                                    if ( ! isset($tts[$realksy . "_" . $uniq]))
                                        break;
                                }
                                $tts[$realksy . "_" . $uniq] = array("id"     => $n->getAttribute("id"), "allsy"  => $allsy, "nchild" => $xpath->query("te", $n)->length);
                            }
                            else {
                                $tts[] = array("id"     => $n->getAttribute("id"), "allsy"  => $allsy, "nchild" => $xpath->query("te", $n)->length);
                            }
                        }
                    } elseif ($n->nodeName == "sy") {
                        $id = str_replace(".", "d", $n->getAttribute("id")) . "d";
                        $hits = "";

                        $sql = "SELECT COUNT(DISTINCT(record_id)) AS hits
                    FROM thit WHERE value = :id";

                        $stmt = $connbas->prepare($sql);
                        $stmt->execute(array(':id'    => $id));
                        $rowbas2 = $stmt->fetch(PDO::FETCH_ASSOC);
                        $stmt->closeCursor();

                        if ($rowbas2)
                            $hits = $rowbas2["hits"];

                        $sy = $sy_list->appendChild($ret->createElement("sy"));

                        $sy->setAttribute("id", $n->getAttribute("id"));
                        $sy->setAttribute("v", $t = $n->getAttribute("v"));
                        $sy->setAttribute("w", $n->getAttribute("w"));
                        $sy->setAttribute("hits", $hits);
                        $sy->setAttribute("lng", $lng = $n->getAttribute("lng"));
                        if (($k = $n->getAttribute("k"))) {
                            $sy->setAttribute("k", $k);
                            //        $t .= " (" . $k . ")";
                        }
                        $sy->setAttribute("t", $t);
                        if ($n->getAttribute("id") == $parm["sel"])
                            $sy->setAttribute("sel", "1");
                    }
                }
                $ts_list->setAttribute("nts", $nts);

                if ($parm["sortsy"] && $parm["piv"])
                    ksort($tts, SORT_STRING);
                if ($parm["debug"])
                    printf("tts : <pre>%s</pre><br/>\n", var_export($tts, true));
                foreach ($tts as $ts) {
                    $newts = $ts_list->appendChild($ret->createElement("ts"));
                    $newts->setAttribute("id", $ts["id"]);
                    $newts->setAttribute("nts", $ts["nchild"]);
                    $newts->appendChild($ret->createTextNode($ts["allsy"]));
                }


                $fullpath_html = $fullpath = "";
                for ($depth = 0, $n = $nodes->item(0); $n; $n = $n->parentNode, $depth -- ) {
                    if ($n->nodeName == "te") {
                        if ($parm["debug"])
                            printf("parent:%s<br/>\n", $n->nodeName);
                        if ($parm["typ"] == "CT" && ($fld = $n->getAttribute("field")) != "") {
                            // la source provient des candidats pour ce champ
                            if ($parm["debug"])
                                printf("field:%s<br/>\n", $fld);

                            $cfield->setAttribute("field", $fld);
                            $cfield->setAttribute("delbranch", $n->getAttribute("delbranch"));

                            $fullpath = " / " . $fld . $fullpath;
                            if ($depth == 0)
                                $fullpath_html = "<span class='path_separator'> / </span><span class='main_term'>" . $fld . "</span>" . $fullpath_html;
                            else
                                $fullpath_html = "<span class='path_separator'> / </span>" . $fld . $fullpath_html;
                            break;
                        }
                        $firstsy = $goodsy = null;
                        for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
                            if ($n2->nodeName == "sy") {
                                $sy = $n2->getAttribute("v");
                                if ( ! $firstsy) {
                                    $firstsy = $sy;
                                    if ($parm["debug"])
                                        printf("fullpath : firstsy='%s' in %s<br/>\n", $firstsy, $n2->getAttribute("lng"));
                                }
                                if ($n2->getAttribute("lng") == $parm["piv"]) {
                                    if ($parm["debug"])
                                        printf("fullpath : found '%s' in %s<br/>\n", $sy, $n2->getAttribute("lng"));
                                    $goodsy = $sy;
                                    break;
                                }
                            }
                        }
                        if ( ! $goodsy)
                            $goodsy = $firstsy;
                        $fullpath = " / " . $goodsy . $fullpath;
                        if ($depth == 0)
                            $fullpath_html = "<span class='path_separator'> / </span><span class='main_term'>" . $goodsy . "</span>" . $fullpath_html;
                        else
                            $fullpath_html = "<span class='path_separator'> / </span>" . $goodsy . $fullpath_html;
                    }
                }
                if ($fullpath == "") {
                    $fullpath = "/";
                    $fullpath_html = "<span class='path_separator'> / </span>";
                }
                $fp = $root->appendchild($ret->createElement("fullpath"));
                $fp->appendChild($ret->createTextNode($fullpath));

                $fp = $root->appendchild($ret->createElement("fullpath_html"));
                $fp->appendChild($ret->createTextNode($fullpath_html));

                // $id = "S" . str_replace(".", "d", substr($nodes->item(0)->getAttribute("id"), 1)) . "d";
                $id = str_replace(".", "d", $nodes->item(0)->getAttribute("id")) . "d";
                $hits = "0";

                $sql = "SELECT COUNT(DISTINCT(record_id)) AS hits
                FROM thit WHERE value = :id";

                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':id'    => $id));
                $rowbas2 = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if ($rowbas2)
                    $hits = $rowbas2["hits"];

                $n = $root->appendchild($ret->createElement("hits"));
                $n->appendChild($ret->createTextNode($hits));

                $hits = "0";
                $sql = "SELECT COUNT(DISTINCT(record_id)) AS hits FROM thit WHERE value LIKE :like";

                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':like'  => $id . '%'));
                $rowbas2 = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if ($rowbas2)
                    $hits = $rowbas2["hits"];

                $n = $root->appendchild($ret->createElement("allhits"));
                $n->appendChild($ret->createTextNode($hits));
            }
        }
    } catch (Exception $e) {

    }
}
if ($parm["debug"])
    print("<pre>" . $ret->saveXML() . "</pre>");
else
    print($ret->saveXML());
