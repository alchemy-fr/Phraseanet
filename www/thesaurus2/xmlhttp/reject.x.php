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
require_once __DIR__ . "/../../../lib/bootstrap.php";
$app = new Application();

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
        $databox = $app['phraseanet.appbox']->get_databox((int) $parm['bid']);
        $connbas = connection::getPDOConnection($app, $parm['bid']);

        $dom = $databox->get_cterms();

        if ($dom) {
            $xpath = new DOMXPath($dom);
            $q = "/cterms//te[@id='" . $parm["id"] . "']";
            if ($parm["debug"])
                print("q:" . $q . "<br/>\n");

            $te = $xpath->query($q)->item(0);
            if ($te) {
                if ($parm["debug"])
                    printf("found te : id=%s<br/>\n", $te->getAttribute("id"));

                rejectBranch($connbas, $te);

                $databox->saveCterms($dom);

                $r = $refresh_list->appendChild($ret->createElement("refresh"));
                $r->setAttribute("id", $te->parentNode->getAttribute("id"));
                $r->setAttribute("type", "CT");
            }
        }
    } catch (Exception $e) {

    }
}
if ($parm["debug"])
    print("<pre>" . $ret->saveXML() . "</pre>");
else
    print($ret->saveXML());

function rejectBranch(connection_pdo &$connbas, &$node)
{
    global $parm;
    if (strlen($oldid = $node->getAttribute("id")) > 1) {
        $node->setAttribute("id", $newid = ("R" . substr($oldid, 1)));

        $thit_oldid = str_replace(".", "d", $oldid) . "d";
        $thit_newid = str_replace(".", "d", $newid) . "d";
        $sql = "UPDATE thit SET value = :new_value WHERE value = :old_value";
        if ($parm["debug"])
            printf("sql: %s<br/>\n", $sql);
        else {
            $stmt = $connbas->prepare($sql);
            $stmt->execute(array(':old_value' => $thit_oldid, ':new_value' => $thit_newid));
            $stmt->closeCursor();
        }
    }
    for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
        if ($n->nodeType == XML_ELEMENT_NODE)
            rejectBranch($connbas, $n);
    }
}
?>
