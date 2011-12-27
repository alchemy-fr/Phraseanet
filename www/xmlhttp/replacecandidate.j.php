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
                'sbid'
                , 'cid'  // candidate (id) to replace
                , 't'  // replacing term
                , 'debug'
);

phrasea::headers(200, true, 'application/json', 'UTF-8', false);

if ($parm['debug'])
  print("<pre>");

$dbname = null;

$result = array('n_recsChanged' => 0); // , 'n_termsDeleted'=>0, 'n_termsReplaced'=>0);

try
{

  $databox = databox::get_instance((int) $parm['sbid']);
  $domth = $databox->get_dom_thesaurus();
  $domct = $databox->get_dom_cterms();

  if ($domth && $domct)
  {
    $xpathct = new DOMXPath($domct);

    $field = null;
    $x = null;

    $xp = '//te[@id="' . $parm['cid'] . '"]/sy';
    $nodes = $xpathct->query($xp);
    if ($nodes->length == 1)
    {
      $sy = $term = $nodes->item(0);

      $candidate = array('a' => $sy->getAttribute('v'), 'u' => $sy->getAttribute('w'));
      if (($k = $sy->getAttribute('k')))
        $candidate['u'] .= ' (' . $k . ')';
      if ($parm['debug'])
        printf("%s : candidate = %s \n", __LINE__, var_export($candidate, true));

      $syid = str_replace('.', 'd', $sy->getAttribute('id')) . 'd';
      $field = $sy->parentNode->parentNode->getAttribute('field');

      // remove candidate from cterms
      $te = $sy->parentNode;
      $te->parentNode->removeChild($te);

      $databox->saveCterms($domct);

      $sql = 'SELECT t.record_id, r.xml
              FROM thit AS t INNER JOIN record AS r USING(record_id)
              WHERE t.value = :syn_id
              ORDER BY record_id';

      $stmt = $connbas->prepare($sql);
      $stmt->execute(array(':syn_id' => $syid));
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      if ($parm['debug'])
        printf("%s : %s \n", __LINE__, $sql);

      $t_rid = array();
      foreach ($rs as $rowbas)
      {
        $rid = $rowbas['record_id'];
        if (!array_key_exists('' . $rid, $t_rid))
          $t_rid['' . $rid] = $rowbas['xml'];
      }
      if ($parm['debug'])
        printf("%s : %s \n", __LINE__, var_export($t_rid, true));

      $replacing = array();
      $parm['t'] = explode(';', $parm['t']);
      foreach ($parm['t'] as $t)
        $replacing[] = simplified($t);
      if ($parm['debug'])
        printf("%s : replacing=%s \n", __LINE__, var_export($replacing, true));


      foreach ($t_rid as $rid => $xml)
      {
        if ($parm['debug'])
          printf("%s rid=%s \n", __LINE__, $rid);
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if (!($dom->loadXML($xml)))
          continue;

        if ($parm['debug'])
          printf("AVANT:\n%s \n", htmlentities($dom->saveXML()));

        // $existed = false;
        $nodetoreplace = null;
        $nodestodelete = array();
        $xp = new DOMXPath($dom);

        $x = '/record/description/' . $field;
        if ($parm['debug'])
          printf("%s x=%s \n", __LINE__, $x);
        $nodes = $xp->query($x);

        $insertBefore = null;
        if ($nodes->length <= 0)
          continue;
//        {
        $insertBefore = $nodes->item($nodes->length - 1);
        if ($parm['debug'])
          printf("%s nodes->length=%s  - insertBefore=%s, nn=%s\n", __LINE__, $nodes->length, var_export($insertBefore, true), $insertBefore->nodeName);
        while (($insertBefore = $insertBefore->nextSibling) && $insertBefore->nodeType != XML_ELEMENT_NODE)
          ;
        if ($parm['debug'] && $insertBefore)
          printf("%s insertBefore=%s , nn=%s \n", __LINE__, var_export($insertBefore, true), $insertBefore->nodeName);

        $t_mval = array();
        foreach ($nodes as $n)
        {
          $value = simplified($n->textContent);
          if (in_array($value['a'], $t_mval))  // a chance to delete doubles
            continue;
          for ($i = 0; $i < 9999 && array_key_exists($value['u'] . '_' . $i, $t_mval); $i++)
            ;
          $t_mval[$value['u'] . '_' . $i] = $value['a'];
          $nodestodelete[] = $n;
        }
        if ($parm['debug'])
          printf("%s : t_mval AVANT = %s \n", __LINE__, var_export($t_mval, true));

        if (($k = array_search($candidate['a'], $t_mval)) !== false)
        {
          unset($t_mval[$k]);
          if ($parm['debug'])
            printf("%s : after unset %s from t_mval %s \n", __LINE__, $k, var_export($t_mval, true));
          foreach ($replacing as $r)
          {
            if (in_array($r['a'], $t_mval))
              continue;
            for ($i = 0; $i < 9999 && array_key_exists($r['u'] . '_' . $i, $t_mval); $i++)
              ;
            $t_mval[$r['u'] . '_' . $i] = $r['a'];
          }
          if ($parm['debug'])
            printf("%s : after replace to t_mval %s \n", __LINE__, var_export($t_mval, true));
        }

        foreach ($nodestodelete as $n)
          $n->parentNode->removeChild($n);

        ksort($t_mval, SORT_STRING);

        if ($insertBefore)
        {
          array_reverse($t_mval);
          foreach ($t_mval as $t)
            $insertBefore->parentNode->insertBefore($dom->createElement($field), $insertBefore)->appendChild($dom->createTextNode($t));
        }
        else
        {
          $desc = $xp->query('/record/description')->item(0);
          foreach ($t_mval as $t)
            $desc->appendChild($dom->createElement($field))->appendChild($dom->createTextNode($t));
        }


        if ($parm['debug'])
          printf("%s : t_mval APRES = %s \n", __LINE__, var_export($t_mval, true));


        if ($parm['debug'])
          printf("APRES:\n%s \n", htmlentities($dom->saveXML()));

        if (!$parm['debug'])
        {
          $sql = 'DELETE FROM idx  WHERE record_id = :record_id';
          $stmt = $connbas->prepare($sql);
          $stmt->execute(array(':record_id' => $rid));
          $stmt->closeCursor();

          $sql = 'DELETE FROM prop WHERE record_id = :record_id';
          $stmt = $connbas->prepare($sql);
          $stmt->execute(array(':record_id' => $rid));
          $stmt->closeCursor();

          $sql = 'DELETE FROM thit WHERE record_id = :record_id';
          $stmt = $connbas->prepare($sql);
          $stmt->execute(array(':record_id' => $rid));
          $stmt->closeCursor();

          $sql = 'UPDATE record
                  SET status=(status & ~3)|4, jeton=' . (JETON_WRITE_META_DOC | JETON_WRITE_META_SUBDEF) . '
                    , xml = :xml
                  WHERE record_id =  :record_id';
          $stmt = $connbas->prepare($sql);
          $stmt->execute(array(':record_id' => $rid, ':xml' => $dom->saveXML()));
          $stmt->closeCursor();
        }
        $result['n_recsChanged']++;
      }
    }
  }
}
catch (Exception $e)
{

}

function simplified($t)
{
  $t = splitTermAndContext($t);
  $unicode = new unicode();
  $su = $unicode->remove_indexer_chars($sa = $t[0]);
  if ($t[1])
  {
    $sa .= ' (' . ($t[1]) . ')';
    $su .= ' (' . $unicode->remove_indexer_chars($t[1]) . ')';
  }

  return(array('a' => $sa, 'u' => $su));
}

if ($parm['debug'])
  var_dump($result);
else
  print(p4string::jsonencode(array('parm' => $parm, 'result' => $result)));

if ($parm['debug'])
  print("</pre>");

function splitTermAndContext($word)
{
  $term = trim($word);
  $context = '';
  if (($po = strpos($term, '(')) !== false)
  {
    if (($pc = strpos($term, ')', $po)) !== false)
    {
      $context = trim(substr($term, $po + 1, $pc - $po - 1));
      $term = trim(substr($term, 0, $po));
    }
    else
    {
      $context = trim(substr($term, $po + 1));
      $term = trim(substr($term, 0, $po));
    }
  }

  return(array($term, $context));
}
