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
$html = $root->appendChild($ret->createElement("html"));

if ($parm["bid"] !== null) {
    $loaded = false;
    try {
        $databox = $app['phraseanet.appbox']->get_databox((int) $parm['bid']);

        if ($parm["typ"] == "CT") {
            $xqroot = "cterms";
            $dom = $databox->get_dom_cterms();
        } else {
            $xqroot = "thesaurus";
            $dom = $databox->get_dom_thesaurus();
        }

        if ($dom) {
            $xpath = new DOMXPath($dom);
            if ($parm["id"] == "T")
                $q = "/thesaurus";
            elseif ($parm["id"] == "C")
                $q = "/cterms";
            else
                $q = "/$xqroot//te[@id='" . $parm["id"] . "']";

            if ($parm["debug"])
                print("q:" . $q . "<br/>\n");

            $node = $xpath->query($q)->item(0);

            getHTML2($node, $ret, $html, 0);
        }
    } catch (Exception $e) {

    }
}
if ($parm["debug"])
    print("<pre>" . $ret->saveXML() . "</pre>");
else
    print($ret->saveXML());

function getHTML(&$dom, &$node, $typ)
{
    $html = new DOMDocument("1.0", "UTF-8");
    $html->standalone = true;
    $html->preserveWhiteSpace = false;
    $root = $html->appendChild($html->createElement("body"));

    getHTML2($node, $html, $root, 0);

    // return($html);
}

function getHTML3($srcnode, $dstdom, $dstnode, $depth)
{
    // printf("in: depth:%s<br/>\n", $depth);

    $allsy = "";
    $nts = 0;
    for ($n = $srcnode->firstChild; $n; $n = $n->nextSibling) {
        if ($n->nodeName == "te" && $depth < 10) {
            $nts ++;

            $id = $n->getAttribute("id");
            $div_the = $dstnode->appendChild($dstdom->createElement("div"));
            $div_the->setAttribute("id", "THE_" . $id);
            $div_the->setAttribute("class", "s_");

            $u = $div_the->appendChild($dstdom->createElement("u"));
            $u->setAttribute("id", "THP_" . $id);

            $div_thb = $dstnode->appendChild($dstdom->createElement("div"));
            $div_thb->setAttribute("id", "THB_" . $id);

            $t = getHTML3($n, $dstdom, $div_thb, $depth + 1);
            if ($t["nts"] == 0) {
                $u->setAttribute("class", "nots");
                $div_thb->setAttribute("class", "ob");
            } else {
                $u->appendChild($dstdom->createTextNode("-"));
                $div_thb->setAttribute("class", "OB");
            }

            $div_the->appendChild($dstdom->createTextNode($t["allsy"]));

            //if(!$div_thb->firstChild)
            //  $div_thb->appendChild($dstdom->createTextNode("-"));
        } elseif ($n->nodeName == "sy")
            $allsy .= ( $allsy ? " ; " : "") . $n->getAttribute("v");
    }
    if ($allsy == "")
        $allsy = "THESAURUS";

    return(array("allsy" => $allsy, "nts"   => $nts));
    // printf("out: depth:%s<br/>\n", $depth);
    // return($depth==0 ? $div_thb : null);
}

function getHTML2($srcnode, $dstdom, $dstnode, $depth)
{
    // printf("in: depth:%s<br/>\n", $depth);

    $allsy = "";
    $nts = 0;
    for ($n = $srcnode->firstChild; $n; $n = $n->nextSibling) {
        if ($n->nodeName == "te" && $depth < 100) {
            $nts ++;

            $id = $n->getAttribute("id");
            $div_the = $dstnode->appendChild($dstdom->createElement("div"));
            $div_the->setAttribute("id", "THE_" . $id);
            $div_the->setAttribute("class", "s_");

            $u = $div_the->appendChild($dstdom->createElement("u"));
            $u->setAttribute("id", "THP_" . $id);

            $div_thb = $dstnode->appendChild($dstdom->createElement("div"));
            $div_thb->setAttribute("id", "THB_" . $id);

            $t = getHTML2($n, $dstdom, $div_thb, $depth + 1);
            if ($t["nts"] == 0) {
                $u->setAttribute("class", "nots");
                $div_thb->setAttribute("class", "ob");
            } else {
                $u->appendChild($dstdom->createTextNode("-"));
                $div_thb->setAttribute("class", "OB");
            }

            $div_the->appendChild($dstdom->createTextNode($t["allsy"]));

            //if(!$div_thb->firstChild)
            //  $div_thb->appendChild($dstdom->createTextNode("-"));
        } elseif ($n->nodeName == "sy")
            $allsy .= ( $allsy ? " ; " : "") . $n->getAttribute("v");
    }
    if ($allsy == "")
        $allsy = "THESAURUS";

    return(array("allsy" => $allsy, "nts"   => $nts));
    // printf("out: depth:%s<br/>\n", $depth);
    // return($depth==0 ? $div_thb : null);
}
