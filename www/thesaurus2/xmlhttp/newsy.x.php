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
                , "pid"
                , "piv"  // lng de consultation (pivot)
                , "sylng" // lng pour le synonyme
                , "t"
                , "k"
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
    $domth = $databox->get_dom_thesaurus();
    $unicode = new unicode();

    if ($domth)
    {
      $xpathth = new DOMXPath($domth);
      if ($parm["pid"] === "T")
        $q = "/thesaurus";
      else
        $q = "/thesaurus//te[@id='" . $parm["pid"] . "']";
      $te = $xpathth->query($q)->item(0);
      if ($te)
      {
        $tenextid = (int) ($te->getAttribute("nextid"));
        $te->setAttribute("nextid", $tenextid + 1);

        $sy = $te->appendChild($domth->createElement("sy"));
        // $syid = "S".substr($te->getAttribute("id"), 1) . "." . $tenextid;
        $syid = $te->getAttribute("id") . "." . $tenextid;
        $sy->setAttribute("id", $syid);
        if ($parm["debug"])
          printf("syid='%s'<br/>\n", $syid);

        if ($parm["sylng"])
          $sy->setAttribute("lng", $parm["sylng"]);
        else
          $sy->setAttribute("lng", "");

        list($v, $k) = splitTermAndContext($parm["t"]);

        $k = trim($k) . trim($parm["k"]);
        $w = $unicode->remove_indexer_chars($v);
        if ($k)
          $v .= " (" . $k . ")";
        $k = $unicode->remove_indexer_chars($k);

        $sy->setAttribute("v", $v);
        $sy->setAttribute("w", $w);
        if ($parm["debug"])
          printf("v='%s' w='%s'<br/>\n", $v, $w);
        if ($k)
        {
          $sy->setAttribute("k", $k);
          if ($parm["debug"])
            printf("k='%s'<br/>\n", $k);
        }

        $databox->saveThesaurus($domth);

        $r = $refresh_list->appendChild($ret->createElement("refresh"));
        $r->setAttribute("type", "TH");
        $pid = $te->parentNode->getAttribute("id");
        if ($pid == "")
          $pid = "T";
        $r->setAttribute("id", $pid);
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

function splitTermAndContext($word)
{
  $term = trim($word);
  $context = "";
  if (($po = strpos($term, "(")) !== false)
  {
    if (($pc = strpos($term, ")", $po)) !== false)
    {
      $context = trim(substr($term, $po + 1, $pc - $po - 1));
      $term = trim(substr($term, 0, $po));
    }
  }

  return(array(trim($term), trim($context)));
}
