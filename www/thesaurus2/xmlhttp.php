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
function xmlhttp($url)
{
    $registry = registry::get_instance();
    $fullurl = $registry->get('GV_ServerName') . $url;
    $xml = http_query::getUrl($fullurl);
    $ret = new DOMDocument();
    $ret->loadXML($xml);

    return($ret);
}

function indentXML(&$dom)
{
    indentXML2($dom, $dom->documentElement, 0, 0);
}

function indentXML2(&$dom, $node, $depth, $ichild)
{
    $tab = str_repeat("\t", $depth);
    $fc = null;
    if ($node->nodeType == XML_ELEMENT_NODE) {
        if ($ichild == 0)
            $node->parentNode->insertBefore($dom->createTextNode($tab), $node);
        else
            $node->parentNode->insertBefore($dom->createTextNode("\n" . $tab), $node);
        $fc = $node->firstChild;
        if ($fc) {
            if ($fc->nodeType == XML_TEXT_NODE && ! $fc->nextSibling) {

            } else {
                $node->insertBefore($dom->createTextNode("\n"), $fc);
                for ($i = 0, $n = $fc; $n; $n = $n->nextSibling, $i ++ ) {
                    indentXML2($dom, $n, $depth + 1, $i);
                }
                $node->appendChild($dom->createTextNode("\n" . $tab));
            }
        }
    } elseif ($node->nodeType == XML_TEXT_NODE) {
        $node->parentNode->insertBefore($dom->createTextNode($tab), $node);
    }
}
