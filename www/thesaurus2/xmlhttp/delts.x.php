<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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
require_once __DIR__ . "/../../../vendor/autoload.php";
$app = new Application();

$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "id"
    , "piv"  // lng de consultation (pivot)
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
        $databox = $app['phraseanet.appbox']->get_databox((int) $parm['bid']);
        $domth = $databox->get_dom_thesaurus();
        $domct = $databox->get_dom_cterms();

        if ($domth && $domct) {
            $xpathth = new DOMXPath($domth);
            $xpathct = new DOMXPath($domct);
            if ($parm["id"] !== "") {    // secu pour pas exploser le thesaurus
                $q = "/thesaurus//te[@id='" . $parm["id"] . "']";
                if ($parm["debug"])
                    printf("q:%s<br/>\n", $q);
                $thnode = $xpathth->query($q)->item(0);
                if ($thnode) {
                    $chgids = array();
                    $pid = $thnode->parentNode->getAttribute("id");
                    if ($pid === "")
                        $pid = "T";

                    moveToDeleted($thnode, $chgids, $parm['bid']);

                    if ($parm["debug"])
                        printf("chgids: %s<br/>\n", var_export($chgids, true));

                    $databox->saveCterms($domct)
                        ->saveThesaurus($domth);

                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("id", $pid);
                    $r->setAttribute("type", "TH");
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

function moveToDeleted(&$thnode, &$chgids, $sbas_id)
{
    global $parm, $root, $ret, $domth, $domct, $xpathct, $refresh_list;

    $nodes = $xpathct->query("/cterms/te[@delbranch='1']");
    if ( ! $nodes || ($nodes->length == 0)) {
        $id = $domct->documentElement->getAttribute("nextid");
        if ($parm["debug"])
            printf("creating 'deleted' branch : id=%s<br/>\n", $id);
        $domct->documentElement->setAttribute("nextid", (int) ($id) + 1);
        $ct = $domct->documentElement->appendChild($domct->createElement("te"));
        $ct->setAttribute("id", "C" . $id);
        $ct->setAttribute("field", _('thesaurus:: corbeille'));
        $ct->setAttribute("nextid", "0");
        $ct->setAttribute("delbranch", "1");

        $r = $refresh_list->appendChild($ret->createElement("refresh"));
        $r->setAttribute("id", "C");
        $r->setAttribute("type", "CT");
    }
    else {
        $ct = $nodes->item(0);
        $r = $refresh_list->appendChild($ret->createElement("refresh"));
        $r->setAttribute("id", $ct->getAttribute("id"));
        $r->setAttribute("type", "CT");
    }
    $teid = (int) ($ct->getAttribute("nextid"));
    $ct->setAttribute("nextid", $teid + 1);

    $newte = $ct->appendChild($domct->importNode($thnode, true));
    $oldid = $newte->getAttribute("id");

    renum($newte, $ct->getAttribute("id") . "." . $teid, $chgids);
    // $newte->setAttribute("id", "R".substr($newte->getAttribute("id"), 1));

    $newid = $ct->getAttribute("id") . "." . $teid;
    $soldid = str_replace(".", "d", $oldid) . "d";
    $snewid = str_replace(".", "d", $newid) . "d";
    $l = strlen($soldid) + 1;

    $connbas = connection::getPDOConnection($app, $sbas_id);

    $sql = "UPDATE thit SET value=CONCAT('$snewid', SUBSTRING(value FROM $l))
          WHERE value LIKE :like";

    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':like' => $soldid . '%'));
    $stmt->closeCursor();

    $thnode->parentNode->removeChild($thnode);

    if ($parm["debug"]) {
        printf("<pre>%s</pre>", $domct->saveXML());
    }
}

function renum($node, $id, &$chgids)
{
    global $parm;
    if ($parm["debug"])
        printf("renum(%s)<br/>\n", $id);
    $oldid = $node->getAttribute("id");
    $newid = $id;
    //if($node->nodeName=="sy")
    //  $newid = "S".substr($newid, 1);
    // $chgids[] = array("oldid"=>$oldid, "newid"=>$newid);
    $node->setAttribute("id", $newid);
    $nchild = 0;
    for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
        if ($n->nodeType == XML_ELEMENT_NODE && ($n->nodeName == "te" || $n->nodeName == "sy")) {
            renum($n, $id . "." . $nchild, $chgids);
            $nchild ++;
        }
    }
    $node->setAttribute("nextid", $nchild);
}
