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

require("../xmlhttp.php");


$request = http_request::getInstance();
$parm = $request->get_parms(
                "bid"
                , "id"
                , "typ"  // "TH" (thesaurus) ou "CT" (cterms)
                , "piv"  // lng de consultation (pivot)
                , "dir"
                , "debug"
);

if ($parm["debug"])
{
  phrasea::headers(200, true, 'text/html', 'UTF-8', true);
}
else
{
  phrasea::headers(200, true, 'text/xml', 'UTF-8', false);
}

$ret = new DOMDocument("1.0", "UTF-8");
$ret->standalone = true;
$ret->preserveWhiteSpace = false;
$root = $ret->appendChild($ret->createElement("result"));
$root->appendChild($ret->createCDATASection(var_export($parm, true)));
$refresh_list = $root->appendChild($ret->createElement("refresh_list"));
if ($parm["bid"] !== null)
{
  $loaded = false;

  try
  {
    $databox = databox::get_instance((int) $parm['bid']);

    if ($parm["typ"] == "CT")
    {
      $xqroot = "cterms";
      $dom = $databox->get_dom_cterms();
    }
    else
    {
      $xqroot = "thesaurus";
      $dom = $databox->get_dom_thesaurus();
    }

    if ($dom)
    {
      $xpath = new DOMXPath($dom);
      $q = "/$xqroot//sy[@id='" . $parm["id"] . "']";
      if ($parm["debug"])
        print("q:" . $q . "<br/>\n");

      $sy0 = $xpath->query($q)->item(0);
      if ($sy0)
      {
        if ($parm["dir"] == 1 && $sy0 && $sy0->previousSibling)
        {
          $sy0->parentNode->insertBefore($sy0, $sy0->previousSibling);
        }
        elseif ($parm["dir"] == -1 && $sy0 && $sy0->nextSibling)
        {
          $sy0->parentNode->insertBefore($sy0->nextSibling, $sy0);
        }

        if ($xqroot == "cterms")
        {
          $databox->saveCterms($dom);
        }
        elseif ($xqroot == "thesaurus")
        {
          $databox->saveThesaurus($dom);
        }

        $url = "thesaurus2/xmlhttp/getterm.x.php";
        $url .= "?bid=" . urlencode($parm["bid"]);
        $url .= "&typ=" . urlencode($parm["typ"]);
        $url .= "&piv=" . urlencode($parm["piv"]);
        $url .= "&id=" . urlencode($sy0->parentNode->getAttribute("id"));
        $url .= "&sel=" . urlencode($parm["id"]);
        $url .= "&nots=1";  // liste des ts inutile

        if ($parm["debug"])
        {
          printf("url: %s<br/>\n", $url);
        }
        $ret = xmlhttp($url); // ecrase le ret inital !
        $root = $ret->getElementsByTagName("result")->item(0);
        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));
        $r = $refresh_list->appendChild($ret->createElement("refresh"));
        $r->setAttribute("id", $sy0->parentNode->parentNode->getAttribute("id"));
        $r->setAttribute("type", $parm["typ"]);
      }
    }
  }
  catch (Exception $e)
  {

  }
}
if ($parm["debug"])
  print("<pre>" . $ret->saveXML() . "</pre>");
else
  print($ret->saveXML());
