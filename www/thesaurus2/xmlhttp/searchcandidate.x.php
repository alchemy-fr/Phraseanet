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
require_once dirname(__FILE__) . "/../../../lib/bootstrap.php";
$registry = registry::get_instance();


$request = http_request::getInstance();
$parm = $request->get_parms(
                "bid"
                , "pid"
                , "t"
                , "k"
                , "piv"
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
$ctlist = $root->appendChild($ret->createElement("candidates_list"));

if ($parm["bid"] !== null)
{
  $loaded = false;
  try
  {
    $databox = databox::get_instance((int) $parm['bid']);

    $domstruct = $databox->get_dom_structure();
    $domth = $databox->get_dom_thesaurus();
    $domct = $databox->get_dom_cterms();
    $unicode = new unicode();

    if ($domstruct && $domth && $domct)
    {
      $xpathth = new DOMXPath($domth);
      $xpathct = new DOMXPath($domct);

      // on cherche les champs d'oe peut provenir un candidat, en fct de l'endroit oe on veut inserer le nouveau terme
      $fields = array();
      $xpathstruct = new DOMXPath($domstruct);
      $nodes = $xpathstruct->query("/record/description/*[@tbranch]");
      for ($i = 0; $i < $nodes->length; $i++)
      {
        $fieldname = $nodes->item($i)->nodeName;
        $tbranch = $nodes->item($i)->getAttribute("tbranch");
        if ($parm["pid"] != "")
          $q = "(" . $tbranch . ")/descendant-or-self::te[@id='" . $parm["pid"] . "']";
        else
          $q = "(" . $tbranch . ")/descendant-or-self::te[not(@id)]";


        $fields[$fieldname] = array("name" => $fieldname, "tbranch" => $tbranch, "cid" => null, "sourceok" => false);

        if(!$tbranch)
          continue;

        $l = $xpathth->query($q)->length;
        if ($parm["debug"])
          printf("field '%s' : %s --: %d nodes<br/>\n", $fieldname, $q, $l);

        if ($l > 0)
        {
          // le pt d'insertion du nvo terme se trouve dans la tbranch du champ,
          // donc ce champ peut etre source de candidats
          $fields[$fieldname]["sourceok"] = true;
        }
        else
        {
          // le pt d'insertion du nvo terme ne se trouve PAS dans la tbranch du champ,
          // donc ce champ ne peut pas etre source de candidats
        }
      }
      // on considere que la source 'deleted' est toujours valide
      $fields["[deleted]"] = array("name" => _('thesaurus:: corbeille'), "tbranch" => null, "cid" => null, "sourceok" => true);

      if (count($fields) > 0)
      {
        // on cherche le terme dans les candidats
        if ($domct = @DOMDocument::loadXML($rowbas["cterms"]))
        {
          $xpathct = new DOMXPath($domct);

          $q = "@w='" . thesaurus::xquery_escape($unicode->remove_indexer_chars($parm["t"])) . "'";
          if ($parm["k"])
          {
            if ($parm["k"] != "*")
              $q .= " and @k='" . thesaurus::xquery_escape($unicode->remove_indexer_chars($parm["k"])) . "'";
          }
          else
          {
            $q .= " and not(@k)";
          }
          $q = "/cterms//te[./sy[$q]]";

          if ($parm["debug"])
            printf("xquery : %s<br/>\n", $q);

          // $root->appendChild($ret->createCDATASection( $q ));
          $nodes = $xpathct->query($q);
          // le terme peut etre present dans plusieurs candidats
          for ($i = 0; $i < $nodes->length; $i++)
          {
            // on a trouve le terme dans les candidats, mais en provenance de quel champ ?.. on remonte au champ candidat
            for ($n = $nodes->item($i)->parentNode; $n && $n->parentNode && $n->parentNode->nodeName != "cterms"; $n = $n->parentNode)
              ;
            if ($parm["debug"])
              printf("proposed in field %s<br/>\n", $n->getAttribute("field"));
            if ($n && array_key_exists($f = $n->getAttribute("field"), $fields))
              $fields[$f]["cid"] = $nodes->item($i)->getAttribute("id");
          }
        }
        if ($parm["debug"])
          printf("fields:<pre>%s</pre><br/>\n", var_export($fields, true));
      }

      foreach ($fields as $kfield => $field)
      {
//                if(!$field["sourceok"] && $field["cid"] === null)
        if ($field["cid"] === null)
          continue;
        $ct = $ctlist->appendChild($ret->createElement("ct"));
        $ct->setAttribute("field", $field["name"]);
        $ct->setAttribute("sourceok", $field["sourceok"] ? "1" : "0");
        if ($field["cid"] !== null)
          $ct->setAttribute("id", $field["cid"]);
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
