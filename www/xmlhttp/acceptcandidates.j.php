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

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once __DIR__ . "/../../vendor/autoload.php";
$app = new Application();

$request = http_request::getInstance();
$parm = $request->get_parms(
    "sbid"
    , "piv"  // pivot language
    , "cid" // candidates
    , "tid"  // where to accept terms
    , "typ"  // "TS"=creer nouvo terme spec. ou "SY" creer simplement synonyme
    , "debug"
);

$ret = array('refresh' => array());
$refresh = array();

$sbas_id = (int) $parm["sbid"];

try {
    $databox = $app['phraseanet.appbox']->get_databox($sbas_id);
    $connbas = $databox->get_connection();

    $domct = $databox->get_dom_cterms();
    if ( ! ($domct instanceof DOMDocument))
        throw new Exception('Unable to load cterms');

    $domth = $databox->get_dom_thesaurus();
    if ( ! ($domth instanceof DOMDocument))
        throw new Exception('Unable to load thesaurus');

    $xpathth = new DOMXPath($domth);
    if ($parm["tid"] == "T")
        $q = "/thesaurus";
    else
        $q = "/thesaurus//te[@id='" . $parm["tid"] . "']";
    if ($parm["debug"])
        printf("qth: %s<br/>\n", $q);
    $parentnode = $xpathth->query($q)->item(0);
    if ( ! $parentnode)
        throw new Exception('Unable to find branch');

    $xpathct = new DOMXPath($domct);
    $ctchanged = $thchanged = false;

    foreach ($parm["cid"] as $cid) {
        $q = "//te[@id='" . $cid . "']";
        if ($parm["debug"])
            printf("qct: %s<br/>\n", $q);
        $ct = $xpathct->query($q)->item(0);
        if ( ! $ct)
            continue;
        if ($parm["typ"] == "TS") {
            // importer tt la branche candidate comme nouveau ts
            $nid = $parentnode->getAttribute("nextid");
            $parentnode->setAttribute("nextid", (int) $nid + 1);

            $oldid = $ct->getAttribute("id");
            $te = $domth->importNode($ct, true);
            $chgids = array();
            if (($pid = $parentnode->getAttribute("id")) == "") {
                $pid = "T" . $nid;
            } else {
                $pid .= "." . $nid;
            }

            renum($te, $pid, $chgids);
            $te = $parentnode->appendChild($te);

            if ($parm["debug"])
                printf("newid=%s<br/>\n", $te->getAttribute("id"));

            $soldid = str_replace(".", "d", $oldid) . "d";
            $snewid = str_replace(".", "d", $pid) . "d";
            $l = strlen($soldid) + 1;
            $sql = "UPDATE thit SET value=CONCAT('$snewid', SUBSTRING(value FROM $l)) WHERE value LIKE :like";
            if ($parm["debug"]) {
                printf("soldid=%s ; snewid=%s<br/>\nsql=%s<br/>\n", $soldid, $snewid, $sql);
            } else {
                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':like' => $soldid . '%'));
                $stmt->closeCursor();
            }

            $refreshid = $parentnode->getAttribute('id');
            $refresh['T' . $refreshid] = array('type'     => 'T', 'sbid'     => $sbas_id, 'id'       => $refreshid);
            $thchanged = true;

            $refreshid = $ct->parentNode->getAttribute("id");
            $refresh['C' . $refreshid] = array('type' => 'C', 'sbid' => $sbas_id, 'id'   => $refreshid);

            $ct->parentNode->removeChild($ct);

            $ctchanged = true;
        } elseif ($parm["typ"] == "SY") {
            // importer tt le contenu de la branche sous la destination
            for ($ct2 = $ct->firstChild; $ct2; $ct2 = $ct2->nextSibling) {
                if ($ct2->nodeType != XML_ELEMENT_NODE || $ct2->nodeName != 'sy')
                    continue;
                if ($parm['debug'])
                    printf("ct2:%s \n", var_export($ct2, true));
                $nid = $parentnode->getAttribute("nextid");
                $parentnode->setAttribute("nextid", (int) $nid + 1);

                $oldid = $ct2->getAttribute("id");
                $te = $domth->importNode($ct2, true);
                $chgids = array();
                if (($pid = $parentnode->getAttribute("id")) == "") {
                    // racine
                    $pid = "T" . $nid;
                } else {
                    $pid .= "." . $nid;
                }

                renum($te, $pid, $chgids);
                $te = $parentnode->appendChild($te);

                if ($parm["debug"])
                    printf("newid=%s<br/>\n", $te->getAttribute("id"));

                $soldid = str_replace(".", "d", $oldid) . "d";
                $snewid = str_replace(".", "d", $pid) . "d";
                $l = strlen($soldid) + 1;
                $sql = "UPDATE thit SET value=CONCAT('$snewid', SUBSTRING(value FROM $l)) WHERE value LIKE :like";
                if ($parm["debug"]) {
                    printf("soldid=%s ; snewid=%s<br/>\nsql=%s<br/>\n", $soldid, $snewid, $sql);
                } else {
                    $stmt = $connbas->prepare($sql);
                    $stmt->execute(array(':like' => $soldid . '%'));
                    $stmt->closeCursor();
                }

                $thchanged = true;
            }

            $refreshid = $parentnode->parentNode->getAttribute("id");
            $refresh['T' . $refreshid] = array('type' => 'T', 'sbid' => $sbas_id, 'id'   => $refreshid);

            $refreshid = $ct->parentNode->getAttribute("id");
            $refresh['C' . $refreshid] = array('type' => 'C', 'sbid' => $sbas_id, 'id'   => $refreshid);

            $ct->parentNode->removeChild($ct);
            $ctchanged = true;
        }
    }
    if ($ctchanged) {
        $databox->saveCterms($domct);
    }
    if ($thchanged) {
        $databox->saveThesaurus($domth);
    }
} catch (Exception $e) {

}

foreach ($refresh as $r)
    $ret['refresh'][] = $r;

if ($parm["debug"])
    print("<pre>" . p4string::jsonencode($ret) . "</pre>");
else {
    phrasea::headers(200, true, 'application/json', 'UTF-8', false);
    print(p4string::jsonencode($ret));
}

function renum($node, $id, &$chgids, $depth = 0)
{
    global $parm;
    if ($parm["debug"])
        printf("renum('%s' -> '%s')<br/>\n", $node->getAttribute("id"), $id);
    $node->setAttribute("id", $id);
    if ($node->nodeType == XML_ELEMENT_NODE && $node->nodeName == "sy")
        $node->setAttribute("lng", $parm['piv']);

    $nchild = 0;
    for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
        if ($n->nodeType == XML_ELEMENT_NODE && ($n->nodeName == "te" || $n->nodeName == "sy")) {
            renum($n, $id . "." . $nchild, $chgids, $depth + 1);
            $nchild ++;
        }
    }
    $node->setAttribute("nextid", $nchild);
}
