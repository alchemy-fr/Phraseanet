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
require_once __DIR__ . "/../../../lib/bootstrap.php";
$appbox = \appbox::get_instance(\bootstrap::getCore());
$registry = registry::get_instance();


$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "id"
    , "typ"  // "TH" (thesaurus) ou "CT" (cterms)
    , "piv"
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
if ($parm["bid"] !== null) {
    $loaded = false;
    try {
        $databox = $appbox->get_databox((int) $parm['bid']);
        $connbas = connection::getPDOConnection($parm['bid']);

        if ($parm["typ"] == "CT") {
            $xqroot = "cterms";
            $dom = $databox->get_dom_cterms();
        } else {
            $xqroot = "thesaurus";
            $dom = $databox->get_dom_thesaurus();
        }

        if ($dom) {
            $xpath = new DOMXPath($dom);
            if ($parm["id"] == "T") {
                $q = "/thesaurus";
            } elseif ($parm["id"] == "C") {
                $q = "/cterms";
            } else {
                $q = "/$xqroot//sy[@id='" . $parm["id"] . "']";
            }
            if ($parm["debug"])
                print("q:" . $q . "<br/>\n");

            $nodes = $xpath->query($q);
            if ($nodes->length > 0) {
                $t = $nodes->item(0)->getAttribute("v");
                if (($k = $nodes->item(0)->getAttribute("k")))
                    $t .= " (" . $k . ")";

                $fullpath_html = "<span class='path_separator'> / </span><span class='main_term'>" . $t . "</span>";
                $fullpath = " / " . $t;

                $sy = $root->appendchild($ret->createElement("sy"));
                $sy->setAttribute("t", $t);
                foreach (array("v", "w", "k", "lng", "id") as $a) {
                    if ($nodes->item(0)->hasAttribute($a))
                        $sy->setAttribute($a, $nodes->item(0)->getAttribute($a));
                }

                for ($depth = -1, $n = $nodes->item(0)->parentNode->parentNode; $n; $n = $n->parentNode, $depth -- ) {
                    if ($n->nodeName == "te") {
                        if ($parm["debug"])
                            printf("parent:%s<br/>\n", $n->nodeName);
                        if ($parm["typ"] == "CT" && ($fld = $n->getAttribute("field")) != "") {
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
                                $t = $n2->getAttribute("v");
                                if (($k = $n2->getAttribute("k"))) {
                                    //                $t .= " (" . $k . ")";
                                }

                                if ( ! $firstsy)
                                    $firstsy = $t;
                                if ($n2->getAttribute("lng") == $parm["piv"]) {
                                    if ($parm["debug"])
                                        printf("fullpath : found '%s' in %s<br/>\n", $sy, $n2->getAttribute("lng"));
                                    $goodsy = $t;
                                    break;
                                }
                            }
                        }
                        if ( ! $goodsy)
                            $goodsy = $firstsy;
                        $fullpath = " / " . $goodsy . $fullpath;
                        $fullpath_html = "<span class='path_separator'> / </span>" . $goodsy . $fullpath_html;
                    }
                }
                $fp = $root->appendchild($ret->createElement("fullpath"));
                $fp->appendChild($ret->createTextNode($fullpath));

                $fp = $root->appendchild($ret->createElement("fullpath_html"));
                $fp->appendChild($ret->createTextNode($fullpath_html));

                // $id = "S" . str_replace(".", "d", substr($nodes->item(0)->getAttribute("id"), 1)) . "d";
                $id = str_replace(".", "d", $nodes->item(0)->getAttribute("id")) . "d";
                $hits = "0";

                $sql = "SELECT COUNT(DISTINCT(record_id)) AS hits FROM thit WHERE value = :id";

                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':id'    => $id));
                $rowbas2 = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if ($parm["debug"])
                    printf("sql: %s<br/>\n", $sql);

                if ($rowbas2)
                    $hits = $rowbas2["hits"];

                $n = $root->appendchild($ret->createElement("hits"));
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
