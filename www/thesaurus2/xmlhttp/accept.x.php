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
require_once __DIR__ . "/../../../lib/bootstrap.php";

$registry = registry::get_instance();

require("../xmlhttp.php");


$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "id"
    // , "typ"    // "TH" (thesaurus) ou "CT" (cterms)
    , "piv"  // lng de consultation (pivot)
    // , "newlng"  // nouveau lng du sy
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
$refresh_list = $root->appendChild($ret->createElement("refresh_list"));
if ($parm["bid"] !== null) {
    $loaded = false;

    try {
        $databox = databox::get_instance((int) $parm['bid']);
        $connbas = connection::getPDOConnection($parm['bid']);

        $dom = $databox->get_dom_cterms();
        $xpath = new DOMXPath($dom);
        $q = "/cterms//te[@id='" . $parm["id"] . "']";
        if ($parm["debug"])
            print("q:" . $q . "<br/>\n");

        $te = $xpath->query($q)->item(0);
        if ($te) {
            if ($parm["debug"])
                printf("found te : id=%s<br/>\n", $te->getAttribute("id"));

            acceptBranch($parm['bid'], $te);

            $databox->saveCterms($dom);

            $r = $refresh_list->appendChild($ret->createElement("refresh"));
            $r->setAttribute("id", $te->parentNode->getAttribute("id"));
            $r->setAttribute("type", "CT");
        }
    } catch (Exception $e) {

    }
}
if ($parm["debug"])
    print("<pre>" . $ret->saveXML() . "</pre>");
else
    print($ret->saveXML());

function acceptBranch($sbas_id, &$node)
{
    global $parm;
    if (strlen($oldid = $node->getAttribute("id")) > 1) {
        $node->setAttribute("id", $newid = ("C" . substr($oldid, 1)));

        $thit_oldid = str_replace(".", "d", $oldid) . "d";
        $thit_newid = str_replace(".", "d", $newid) . "d";
        $sql = "UPDATE thit SET value = thit_new WHERE value = :thit_old";
        if ($parm["debug"])
            printf("sql: %s<br/>\n", $sql);
        else {
            try {
                $connbas = connection::getPDOConnection($sbas_id);
                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':thit_new' => $thit_newid, 'thit_old'  => $thit_oldid));
                $stmt->closeCursor();
            } catch (Exception $e) {

            }
        }
    }
    for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
        if ($n->nodeType == XML_ELEMENT_NODE)
            acceptBranch($sbas_id, $n);
    }
}
