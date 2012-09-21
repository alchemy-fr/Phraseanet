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
    , "pid"
    , "t"
    , "k"
    , "sylng"
    , "reindex"  // '0' (non) ou '1' (oui = status 'e reindexer thesaurus = status &= ~2)
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
        $connbas = connection::getPDOConnection($app, $parm['bid']);
        $databox = $appbox->get_databox((int) $parm['bid']);
        $domth = $databox->get_dom_thesaurus();
        $unicode = new unicode();

        if ($domth) {
            $xpathth = new DOMXPath($domth);
            if ($parm["pid"] === "T")
                $q = "/thesaurus";
            else
                $q = "/thesaurus//te[@id='" . $parm["pid"] . "']";
            $parentnode = $xpathth->query($q)->item(0);
            if ($parentnode) {
                $nid = $parentnode->getAttribute("nextid");
                $parentnode->setAttribute("nextid", (int) $nid + 1);
                $te = $parentnode->appendChild($domth->createElement("te"));
                if ($parm["pid"] === "T")
                    $te->setAttribute("id", $teid = "T" . ($nid));
                else
                    $te->setAttribute("id", $teid = ($parm["pid"] . "." . $nid));
                $te->setAttribute("nextid", "1");
                $sy = $te->appendChild($domth->createElement("sy"));
                $sy->setAttribute("id", $teid . ".0");
                if ($parm["sylng"])
                    $sy->setAttribute("lng", $parm["sylng"]);
                else
                    $sy->setAttribute("lng", "");

                list($v, $k) = splitTermAndContext($parm["t"]);
                $k = trim($k) . trim($parm["k"]);
                if ($parm["debug"])
                    printf("k='%s'<br/>\n", $k);
                $w = $unicode->remove_indexer_chars($v);
                if ($k)
                    $v .= " (" . $k . ")";
                $k = $unicode->remove_indexer_chars($k);

                $sy->setAttribute("v", $v);
                $sy->setAttribute("w", $w);
                if ($parm["debug"])
                    printf("v='%s' w='%s'<br/>\n", $v, $w);
                if ($k) {
                    $sy->setAttribute("k", $k);
                    if ($parm["debug"])
                        printf("k='%s'<br/>\n", $k);
                }

                $databox->saveThesaurus($domth);

                if ($parm["reindex"] == "1") {
                    $sql = "UPDATE record SET status=status & ~2";
                    $stmt = $connbas->prepare($sql);
                    $stmt->execute();
                    $stmt->closeCursor();
                }

                $r = $refresh_list->appendChild($ret->createElement("refresh"));
                $r->setAttribute("type", "TH");
                $r->setAttribute("id", $parm["pid"]);
            }
        }
    } catch (Exception $e) {

    }
}
if ($parm["debug"])
    print("<pre>" . htmlentities($ret->saveXML()) . "</pre>");
else
    print($ret->saveXML());

function splitTermAndContext($word)
{
    $term = trim($word);
    $context = "";
    if (($po = strpos($term, "(")) !== false) {
        if (($pc = strpos($term, ")", $po)) !== false) {
            $context = trim(substr($term, $po + 1, $pc - $po - 1));
            $term = trim(substr($term, 0, $po));
        }
    }

    return(array($term, $context));
}
