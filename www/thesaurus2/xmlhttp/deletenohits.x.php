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
    , "pid"
    , 'typ'
    , 'id'
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
        $databox = $app['phraseanet.appbox']->get_databox((int) $parm['bid']);
        $connbas = connection::getPDOConnection($app, $parm['bid']);

        $s_thits = '';
        $sql = "SELECT DISTINCT value FROM thit";

        $stmt = $connbas->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $rowbas) {
            $s_thits .= ( str_replace('d', '.', $rowbas['value']) . ';');
        }

        if ($parm['typ'] == 'CT') {
            $dom = $databox->get_dom_cterms();
        } else {
            $dom = $databox->get_dom_thesaurus();
        }

        if ($dom) {
            $xpath = new DOMXPath($dom);
            if ($parm["id"] == "T")
                $q = "/thesaurus";
            elseif ($parm["id"] == "C")
                $q = "/cterms";
            else
                $q = "//te[@id='" . $parm["id"] . "']";
            if (($znode = $xpath->query($q)->item(0))) {
                $nodestodel = array();
                $root->setAttribute('n_nohits', (string) (delete_nohits($znode, $s_thits, $nodestodel)));
                foreach ($nodestodel as $n)
                    $n->parentNode->removeChild($n);

                if ($parm['debug'])
                    printf("<pre>%s</pre>", $dom->saveXML());

                if ($parm['typ'] == 'CT') {
                    $databox->saveCterms($dom);
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

function delete_nohits($node, &$s_thits, &$nodestodel)
{
    global $parm;
    $ret = 0;
    if ($node->nodeType == XML_ELEMENT_NODE) { // && $node->nodeName=='te')
        $id = $node->getAttribute('id') . '.';

        if ((strpos($s_thits, $id)) === false && ! $node->getAttribute('field')) {
            // this id has no hits, neither any of his children
            $nodestodel[] = $node;
            $ret = 1;
        } else {
            // this id (or a child) has hit, must check children
            for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
                if ($n->nodeType == XML_ELEMENT_NODE)
                    $ret += delete_nohits($n, $s_thits, $nodestodel);
            }
        }
        if ($parm['debug'])
            printf("%s : %d<br/>\n", $id, $ret);
    }

    return($ret);
}
