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

$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "id"
    , "sortsy" // trier la liste des sy (="1") ou pas
    , "debug"
);

$parm['lng'] = $Core->getLocale();

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
// $ts_list = $root->appendChild($ret->createElement("ts_list"));
// $sy_list = $root->appendChild($ret->createElement("sy_list"));
$html = $root->appendChild($ret->createElement("html"));
if ($parm["bid"] !== null) {
    $loaded = false;

    $xml = trim($rowbas["xml"]);
    $databox = databox::get_instance((int) $parm['bid']);
    $dom = $databox->get_dom_thesaurus();
    if ($dom) {
        $xpath = $databox->get_xpath_thesaurus();
        if ($parm["id"] == "T") {
            $q = "/thesaurus";
        } else {
            $q = "/thesaurus//te[@id='" . $parm["id"] . "']";
        }
        if ($parm["debug"])
            print("q:" . $q . "<br/>\n");

        $nodes = $xpath->query($q);
        if ($nodes->length > 0) {
            $nts = 0;
            $tts = array();
            // on dresse la liste des termes specifiques avec comme cle le synonyme dans la langue pivot
            for ($n = $nodes->item(0)->firstChild; $n; $n = $n->nextSibling) {
                if ($n->nodeName == "te") {
                    $nts ++;
                    $allsy = "";
                    $tsy = array();
                    $firstksy = null;
                    $ksy = $realksy = null;
                    // on liste les sy pour fabriquer la cle
                    for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
                        if ($n2->nodeName == "sy") {
                            $lng = $n2->getAttribute("lng");
                            $t = $n2->getAttribute("v");
                            $ksy = $n2->getAttribute("w");
                            if ($k = $n2->getAttribute("k")) {
                                //          $t .= " ($k)";
                                $ksy .= " ($k)";
                            }
                            if ( ! $firstksy)
                                $firstksy = $ksy;
                            if ( ! $realksy && $parm["lng"] && $lng == $parm["lng"]) {
                                $realksy = $ksy;
                                // $allsy = "<b>" . $t . "</b>" . ($allsy ? " ; ":"") . $allsy;
                                $allsy = $t . ($allsy ? " ; " : "") . $allsy;

                                array_push($tsy, array("id" => $n2->getAttribute("id"), "sy" => $t));
                            } else {
                                $allsy .= ( $allsy ? " ; " : "") . $t;
                                array_push($tsy, array("id"     => $n2->getAttribute("id"), "sy"     => $t));
                            }
                        }
                    }
                    if ( ! $realksy)
                        $realksy = $firstksy;

                    if ($parm["sortsy"] && $parm["lng"]) {
                        for ($uniq = 0; $uniq < 9999; $uniq ++ ) {
                            if ( ! isset($tts[$realksy . "_" . $uniq]))
                                break;
                        }
                        $tts[$realksy . "_" . $uniq] = array("id"     => $n->getAttribute("id"), "allsy"  => $allsy, "nchild" => $xpath->query("te", $n)->length, "tsy"    => $tsy);
                    }
                    else {
                        $tts[] = array("id"     => $n->getAttribute("id"), "allsy"  => $allsy, "nchild" => $xpath->query("te", $n)->length, "tsy"    => $tsy);
                    }
                } elseif ($n->nodeName == "sy") {

                }
            }

            if ($parm["sortsy"] && $parm["lng"])
                ksort($tts, SORT_STRING);
            if ($parm["debug"])
                printf("tts : <pre>%s</pre><br/>\n", var_export($tts, true));

            $zhtml = "";
            $bid = $parm["bid"];
            foreach ($tts as $ts) {
                $tid = $ts["id"];
                $t = $ts["allsy"];
                $lt = "";
                foreach ($ts["tsy"] as $sy) {
                    $lt .= ( $lt ? " ; " : "");
                    $lt .= "<i id='GL_W." . $bid . "." . $sy["id"] . "'>";
                    $lt .= $sy["sy"];
                    $lt .= "</i>";
                }
                $zhtml .= "<p id='TH_T." . $bid . "." . $tid . "'>";
                if ($ts["nchild"] > 0) {
                    $zhtml .= "<u id='TH_P." . $bid . "." . $tid . "'>+</u>";
                    $zhtml .= $lt;
                    $zhtml .= "</p>";
                    $zhtml .= "<div id='TH_K." . $bid . "." . $tid . "' class='c'>";
                    $zhtml .= "loading";
                    $zhtml .= "</div>";
                } else {
                    $zhtml .= "<u class='w'> </u>";
                    $zhtml .= $lt;
                    $zhtml .= "</p>";
                }
            }
            $html->appendChild($ret->createTextNode($zhtml));
        }
    }
}
if ($parm["debug"])
    print("<pre>" . htmlentities($ret->saveXML()) . "</pre>");
else
    print($ret->saveXML());
