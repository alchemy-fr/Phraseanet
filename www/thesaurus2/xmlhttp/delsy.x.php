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
        $connbas = connection::getPDOConnection($parm['bid']);
        $databox = databox::get_instance((int) $parm['bid']);
        $domct = $databox->get_dom_cterms();
        $dom = $databox->get_dom_thesaurus();

        if ($parm["typ"] == "CT") {
            $xqroot = "cterms";
        } else {
            $xqroot = "thesaurus";
        }

        if ($parm["debug"])
            print("sql:" . $sql . "<br/>\n");

        if ($dom && $domct) {
            $xpath = new DOMXPath($dom);
            $q = "/$xqroot//sy[@id='" . $parm["id"] . "']";

            if ($parm["debug"])
                print("q:" . $q . "<br/>\n");

            $sy0 = $xpath->query($q)->item(0);
            if ($sy0) {
                $xpathct = new DOMXPath($domct);

                // on cherche la branche 'deleted' dans les cterms
                $nodes = $xpathct->query("/cterms/te[@delbranch='1']");
                if ( ! $nodes || ($nodes->length == 0)) {
                    // 'deleted' n'existe pas, on la cree
                    $id = $domct->documentElement->getAttribute("nextid");
                    if ($parm["debug"])
                        printf("creating 'deleted' branch : id=%s<br/>\n", $id);
                    $domct->documentElement->setAttribute("nextid", (int) ($id) + 1);
                    $del = $domct->documentElement->appendChild($domct->createElement("te"));
                    $del->setAttribute("id", "C" . $id);
                    $del->setAttribute("field", _('thesaurus:: corbeille'));
                    $del->setAttribute("nextid", "0");
                    $del->setAttribute("delbranch", "1");

                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("id", "C");
                    $r->setAttribute("type", "CT");
                }
                else {
                    // 'deleted' existe
                    $del = $nodes->item(0);
                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("id", $del->getAttribute("id"));
                    $r->setAttribute("type", "CT");
                }

                // on cree une branche 'te'
                $oldid = $sy0->getAttribute("id");
                $refrid = $sy0->parentNode->parentNode->getAttribute("id");
                $delid = $del->getAttribute("id");
                $delteid = (int) ($del->getAttribute("nextid"));

                if ($parm["debug"])
                    printf("delid=$delid ; delteid=$delteid <br/>\n");

                $del->setAttribute("nextid", $delteid + 1);
                $delte = $del->appendChild($domct->createElement("te"));
                $delte->setAttribute("id", $delid . "." . $delteid);
                $delte->setAttribute("nextid", "1");

                $delsy = $delte->appendChild($domct->createElement("sy"));
                $delsy->setAttribute("id", $newid = ($delid . "." . $delteid . ".0"));
                // $delsy->setAttribute("id", $newid = ($delid . "." . $delteid));
                $delsy->setAttribute("lng", $sy0->getAttribute("lng"));
                $delsy->setAttribute("v", $sy0->getAttribute("v"));
                $delsy->setAttribute("w", $sy0->getAttribute("w"));
                if ($sy0->hasAttribute("k"))
                    $delsy->setAttribute("k", $sy0->getAttribute("k"));

                $te = $sy0->parentNode;
                $te->removeChild($sy0);

                $sql_oldid = str_replace(".", "d", $oldid) . "d";
                $sql_newid = str_replace(".", "d", $newid) . "d";

                $sql = "UPDATE thit SET value = :new_id WHERE value = :old_id";

                if ($parm["debug"])
                    printf("sql: %s<br/>\n", $sql);
                else {
                    $stmt = $connbas->prepare($sql);
                    $stmt->execute(array(':new_id' => $sql_newid, ':old_id' => $sql_oldid));
                    $stmt->closeCursor();
                }

                $sql = array();

                $databox->saveCterms($domct);
                if ($parm["typ"] == "CT") {
                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("type", "CT");
                    if ($refrid)
                        $r->setAttribute("id", $refrid);
                    else
                        $r->setAttribute("id", "C");
                }
                else {
                    $xmlct = str_replace(array("\r", "\n", "\t"), array("", "", ""), $domct->saveXML());
                    $xmlte = str_replace(array("\r", "\n", "\t"), array("", "", ""), $dom->saveXML());

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
                // $url .= "&sel=" . urlencode($parm["id"]);
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
