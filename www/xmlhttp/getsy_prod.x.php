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
require_once __DIR__ . "/../../lib/bootstrap.php";
$app = new Application();
$appbox = $app['phraseanet.appbox'];
$registry = $app['phraseanet.registry'];

$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "id"
    , "debug"
);

phrasea::headers(200, true, 'text/xml', 'UTF-8', false);

$ret = new DOMDocument("1.0", "UTF-8");
$ret->standalone = true;
$ret->preserveWhiteSpace = false;
$root = $ret->appendChild($ret->createElement("result"));
$root->appendChild($ret->createCDATASection(var_export($parm, true)));

if ($parm["bid"] !== null) {
    $loaded = false;

    $databox = $appbox->get_databox((int) $parm['bid']);
    $dom = $databox->get_dom_thesaurus();

    if ($dom) {
        $xpath = $databox->get_xpath_thesaurus();
        $q = "/thesaurus//sy[@id='" . $parm["id"] . "']";
        if ($parm["debug"])
            print("q:" . $q . "<br/>\n");

        $nodes = $xpath->query($q);
        if ($nodes->length > 0) {
            $n2 = $nodes->item(0);
            $root->setAttribute("t", $n2->getAttribute("v"));
        }
    }
}
if ($parm["debug"])
    print("<pre>" . $ret->saveXML() . "</pre>");
else
    print($ret->saveXML());
