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
    , "piv"  // lng de consultation (pivot)
    , "typ"  // "TH" (thesaurus) ou "CT" (cterms)
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
// $sy_list      = $root->appendChild($ret->createElement("sy_list"));
$refresh_list = $root->appendChild($ret->createElement("refresh_list"));

if ($parm["bid"] !== null) {
    $loaded = false;
    try {
        $databox = databox::get_instance((int) $parm['bid']);
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
            $q = "/$xqroot//te[@id='" . $parm["id"] . "']";

            if ($parm["debug"])
                print("q:" . $q . "<br/>\n");

            $sy0 = $xpath->query($q)->item(0);
            if ($sy0) {
                $oldid = $sy0->getAttribute("id");
                $refrid = $sy0->parentNode->getAttribute("id");

                if ($parm["debug"])
                    print("oldid=$oldid ; refrid=$refrid<br/>\n");

                $te = $sy0->parentNode;
                $te->removeChild($sy0);

                $xml_oldid = str_replace(".", "d", $oldid) . "d";
                $sql = "DELETE FROM thit WHERE value LIKE :like";

                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':like' => $xml_oldid . '%'));
                $stmt->closeCursor();

                if ($parm["typ"] == "CT") {
                    $databox->saveCterms($dom);

                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("type", "CT");
                    $r->setAttribute("id", $refrid);
                } else {
                    $databox->saveThesaurus($dom);

                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("type", "TH");
                    if ($refrid)
                        $r->setAttribute("id", $refrid);
                    else
                        $r->setAttribute("id", "T");
                }

                $url = "thesaurus2/xmlhttp/getterm.x.php";
                $url .= "?bid=" . urlencode($parm["bid"]);
                $url .= "&typ=" . urlencode($parm["typ"]);
                $url .= "&piv=" . urlencode($parm["piv"]);
                $url .= "&id=" . urlencode($te->getAttribute("id"));
                $url .= "&nots=1";  // liste des ts inutile
                $ret2 = xmlhttp($url);
                if ($sl = $ret2->getElementsByTagName("sy_list")->item(0)) {
                    $sl = $ret->importNode($sl, true);
                    $sy_list = $root->appendChild($sl);
                }

                if ($parm["debug"]) {
                    printf("url: %s<br/>\n", $url);
                    printf("<pre>" . $ret2->saveXML() . "</pre>");
                }
            }
        }
    } catch (Exception $e) {

    }
}
if ($parm["debug"]) {
    print("<pre>" . $ret->saveXML() . "</pre>");
    print("</body></html>");
}
else
    print($ret->saveXML());
