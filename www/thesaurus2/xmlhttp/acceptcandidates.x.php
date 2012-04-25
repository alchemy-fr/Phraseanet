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
require_once __DIR__ . "/../../../lib/bootstrap.php";

$registry = registry::get_instance();


$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "piv"
    , "cid"
    , "pid"
    , "typ"  // "TS"=creer nouvo terme spec. ou "SY" creer simplement synonyme
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
// $ct_accepted = $root->appendChild($ret->createElement("ct_accepted"));
$refresh_list = $root->appendChild($ret->createElement("refresh_list"));

if ($parm["bid"] !== null) {
    $loaded = false;

    try {
        $databox = databox::get_instance((int) $parm['bid']);
        $connbas = connection::getPDOConnection($parm['bid']);

        $domct = $databox->get_dom_cterms();
        $domth = $databox->get_dom_thesaurus();

        if ($domct !== false && $domth !== false) {
            $xpathth = new DOMXPath($domth);
            if ($parm["pid"] == "T")
                $q = "/thesaurus";
            else
                $q = "/thesaurus//te[@id='" . $parm["pid"] . "']";
            if ($parm["debug"])
                printf("qth: %s<br/>\n", $q);
            $parentnode = $xpathth->query($q)->item(0);
            if ($parentnode) {
                $xpathct = new DOMXPath($domct);
                $ctchanged = $thchanged = false;

                $icid = 0;
                foreach ($parm["cid"] as $cid) {
                    $q = "//te[@id='" . $cid . "']";
                    if ($parm["debug"])
                        printf("qct: %s<br/>\n", $q);
                    $ct = $xpathct->query($q)->item(0);
                    if ($ct) {
                        if ($parm["typ"] == "TS") {
                            // importer tt la branche candidate comme nouveau ts
                            $nid = $parentnode->getAttribute("nextid");
                            $parentnode->setAttribute("nextid", (int) $nid + 1);

                            $oldid = $ct->getAttribute("id");
                            $te = $domth->importNode($ct, true);
                            $chgids = array();
                            if (($pid = $parentnode->getAttribute("id")) == "")
                                $pid = "T" . $nid;
                            else
                                $pid .= "." . $nid;

                            renum($te, $pid, $chgids);
                            $te = $parentnode->appendChild($te);

                            if ($parm["debug"])
                                printf("newid=%s<br/>\n", $te->getAttribute("id"));

                            $soldid = str_replace(".", "d", $oldid) . "d";
                            $snewid = str_replace(".", "d", $pid) . "d";
                            $l = strlen($soldid) + 1;

                            $sql = "UPDATE thit
                      SET value = CONCAT('$snewid', SUBSTRING(value FROM $l))
                      WHERE value LIKE :like";

                            if ($parm["debug"])
                                printf("soldid=%s ; snewid=%s<br/>\nsql=%s<br/>\n", $soldid, $snewid, $sql);
                            else {
                                $stmt = $connbas->prepare($sql);
                                $stmt->execute(array(':like' => $soldid . '%'));
                                $stmt->closeCursor();
                            }

                            if ($icid == 0) { // on update la destination une seule fois
                                $r = $refresh_list->appendChild($ret->createElement("refresh"));
                                $r->setAttribute("id", $parentnode->getAttribute("id"));
                                $r->setAttribute("type", "TH");
                            }
                            $thchanged = true;

                            $r = $refresh_list->appendChild($ret->createElement("refresh"));
                            $r->setAttribute("id", $ct->parentNode->getAttribute("id"));
                            $r->setAttribute("type", "CT");

                            $ct->parentNode->removeChild($ct);

                            $ctchanged = true;
                        } elseif ($parm["typ"] == "SY") {
                            // importer tt le contenu de la branche sous la destination
                            for ($ct2 = $ct->firstChild; $ct2; $ct2 = $ct2->nextSibling) {
                                if ($ct2->nodeType != XML_ELEMENT_NODE)
                                    continue;

                                $nid = $parentnode->getAttribute("nextid");
                                $parentnode->setAttribute("nextid", (int) $nid + 1);

                                $oldid = $ct2->getAttribute("id");
                                $te = $domth->importNode($ct2, true);
                                $chgids = array();
                                if (($pid = $parentnode->getAttribute("id")) == "")
                                    $pid = "T" . $nid;
                                else
                                    $pid .= "." . $nid;

                                renum($te, $pid, $chgids);
                                $te = $parentnode->appendChild($te);

                                if ($parm["debug"])
                                    printf("newid=%s<br/>\n", $te->getAttribute("id"));

                                $soldid = str_replace(".", "d", $oldid) . "d";
                                $snewid = str_replace(".", "d", $pid) . "d";
                                $l = strlen($soldid) + 1;

                                $sql = "UPDATE thit
                        SET value = CONCAT('$snewid', SUBSTRING(value FROM $l))
                        WHERE value LIKE :like";

                                if ($parm["debug"])
                                    printf("soldid=%s ; snewid=%s<br/>\nsql=%s<br/>\n", $soldid, $snewid, $sql);
                                else {
                                    $stmt = $connbas->prepare($sql);
                                    $stmt->execute(array(':like' => $soldid . '%'));
                                    $stmt->closeCursor();
                                }

                                $thchanged = true;
                            }
                            if ($icid == 0) { // on update la destination une seule fois
                                $r = $refresh_list->appendChild($ret->createElement("refresh"));
                                $r->setAttribute("id", $parentnode->parentNode->getAttribute("id"));
                                $r->setAttribute("type", "TH");
                            }
                            $r = $refresh_list->appendChild($ret->createElement("refresh"));
                            $r->setAttribute("id", $ct->parentNode->getAttribute("id"));
                            $r->setAttribute("type", "CT");

                            $ct->parentNode->removeChild($ct);
                            $ctchanged = true;
                        }
                        $icid ++;
                    }
                }
                if ($ctchanged) {
                    $databox->saveCterms($domct);
                }
                if ($thchanged) {
                    $databox->saveThesaurus($domth);
                }
            }
        }
    } catch (Exception $e) {

    }
}
if ($parm["debug"])
    print("<pre>" . $ret->saveXML() . "</pre>");
else
    print($ret->saveXML());

function renum($node, $id, &$chgids, $depth = 0)
{
    global $parm;
    if ($parm["debug"])
        printf("renum('%s' -> '%s')<br/>\n", $node->getAttribute("id"), $id);
    $node->setAttribute("id", $id);
    $nchild = 0;
    for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
        if ($n->nodeType == XML_ELEMENT_NODE && ($n->nodeName == "te" || $n->nodeName == "sy")) {
            renum($n, $id . "." . $nchild, $chgids, $depth + 1);
            $nchild ++;
        }
    }
    $node->setAttribute("nextid", $nchild);
}
