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
require_once __DIR__ . "/../../lib/bootstrap.php";

$registry = registry::get_instance();

$request = http_request::getInstance();
$parm = $request->get_parms(
                "sbid"
                , "id"
                , "piv"
                , "acf"  // si TH, verifier si on accepte les candidats en provenance de ce champ
                , "debug"
);

$json = Array();

if ($parm["sbid"] !== null)
{
  $loaded = false;
  $databox = databox::get_instance((int) $parm['sbid']);

  $dom_thesau = $databox->get_dom_thesaurus();
  $meta = $databox->get_meta_structure();

  if ($dom_thesau)
  {
    $xpath = new DOMXPath($dom_thesau);

    $json['cfield'] = $parm["acf"];

    // on doit verifier si le terme demande est accessible e partir de ce champ acf
    if ($parm["acf"] == '*')
    {
      // le champ "*" est la corbeille, il est toujours accepte
      $json['acceptable'] = true;
    }
    else
    {
      // le champ est teste d'apres son tbranch
      if ($meta && ($databox_field = $meta->get_element_by_name($parm['acf'])))
      {
        $tbranch = $databox_field->get_tbranch();
        $q = "(" . $tbranch . ")/descendant-or-self::te[@id='" . $parm["id"] . "']";

        if ($parm["debug"])
          printf("tbranch-q = \" $q \" <br/>\n");

        $nodes = $xpath->query($q);

        $json['acceptable'] = ($nodes->length > 0);
      }
    }


    if ($parm["id"] == "T")
    {
      $q = "/thesaurus";
    }
    else
    {
      $q = "/thesaurus//te[@id='" . $parm["id"] . "']";
    }
    if ($parm["debug"])
      print("q:" . $q . "<br/>\n");

    $nodes = $xpath->query($q);
    $json['found'] = $nodes->length;

    if ($nodes->length > 0)
    {
      $fullpath_html = $fullpath = "";
      for ($depth = 0, $n = $nodes->item(0); $n; $n = $n->parentNode, $depth--)
      {
        if ($n->nodeName == "te")
        {
          if ($parm["debug"])
            printf("parent:%s<br/>\n", $n->nodeName);
          $firstsy = $goodsy = null;
          for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling)
          {
            if ($n2->nodeName == "sy")
            {
              $sy = $n2->getAttribute("v");
              if (!$firstsy)
              {
                $firstsy = $sy;
                if ($parm["debug"])
                  printf("fullpath : firstsy='%s' in %s<br/>\n", $firstsy, $n2->getAttribute("lng"));
              }
              if ($n2->getAttribute("lng") == $parm["piv"])
              {
                if ($parm["debug"])
                  printf("fullpath : found '%s' in %s<br/>\n", $sy, $n2->getAttribute("lng"));
                $goodsy = $sy;
                break;
              }
            }
          }
          if (!$goodsy)
            $goodsy = $firstsy;
          $fullpath = " / " . $goodsy . $fullpath;
          if ($depth == 0)
            $fullpath_html = "<span class='path_separator'> / </span><span class='main_term'>" . $goodsy . "</span>" . $fullpath_html;
          else
            $fullpath_html = "<span class='path_separator'> / </span>" . $goodsy . $fullpath_html;
        }
      }
      if ($fullpath == "")
      {
        $fullpath = "/";
        $fullpath_html = "<span class='path_separator'> / </span>";
      }
      $json['fullpath'] = $fullpath;
      $json['fullpath_html'] = $fullpath_html;
    }
  }
}
phrasea::headers(200, true, 'application/json', 'UTF-8', false);
print(p4string::jsonencode($json));
