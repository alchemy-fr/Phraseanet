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
require_once dirname(__FILE__) . "/../lib/bootstrap.php";

$registry = registry::get_instance();
require($registry->get('GV_RootPath') . "lib/classes/deprecated/getargs.php");  // le parser d'arguments de la ligne de commande

function printHelp(&$argt, &$conn)
{
  print_usage($argt);
}

$argt = array(
    "--help" => array("set" => false, "values" => array(), "usage" => " : this help")
    , "--sbas-id" => array("set" => false, "values" => array(), "usage" => "=sbas_id : sbas_id to check")
    , "--field" => array("set" => false, "values" => array(), "usage" => "(=field |  : delete this field from records")
    , "--showstruct" => array("set" => false, "values" => array(), "usage" => "")
);

function help()
{
  global $argv;
  printf("usage: %s [options]\n", $argv[0]);
  print("options:\n");
  print("\t--help                     : this help\n");
  print("\t--sbas=sbas_id             : sbas to change (if --help, list fields)\n");
  print("\t--showstruct               : show structure changes and quit\n");
  print("\t--field=fieldname          : delete fieldname from records\n");
  print("\t--field=\"oldname:newname\"  : rename field oldname to newname into records\n");
  print("\t[--field=...]              : --field=... can be repeated\n");
}

// on commence par se conncter e application box
$allbas = array();

$conn = connection::getPDOConnection();

$sql = "SELECT * FROM sbas";
$stmt = $conn->prepare($sql);
$stmt->execute();
$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

foreach ($rs as $tmprow)
{
  $allbas["B" . $tmprow["sbas_id"]] = $tmprow;
}

$error = false;

if (!parse_cmdargs($argt, $err))
{
  help();
  print($err);
  die();
}

if ($argt["--help"]["set"])
{
  help();
  $error = true;
}

if (!$argt['--sbas-id']['set'])
{
  print("missing option 'sbas-id'.\n");
  $error = true;
}

$fields = null;
if ($argt['--field']['set'])
{
  foreach ($argt["--field"]["values"] as $f)
  {
    $f = explode(':', $f);
    $f[] = null;
    $fields[] = array('from' => $f[0], 'to' => $f[1]);
  }
}

$domstruct = null;

if ($argt["--sbas-id"]["set"])
{
  $sbas_id = $argt["--sbas-id"]["values"][0];
  // sauf erreur, on a l'adresse du serveur distant
  $row = null;
  if (array_key_exists("B" . $sbas_id, $allbas))
    $row = $allbas["B" . $sbas_id];
  if ($row)
  {
    try
    {
      $databox = databox::get_instance($sbas_id);
      $tfields = array();

      if ($argt["--help"]["set"])
        echo("fields of sbas=" . $sbas_id . " :\n");

      $domstruct = $databox->get_dom_structure();
      $xp = $databox->get_xpath_structure();

      if ($domstruct)
      {
        $xp = new DOMXPath($domstruct);

        $xf = @$xp->query('/record/description/*');
        foreach ($xf as $f)
        {
          $tfields[] = $f->nodeName;
          if ($argt["--help"]["set"])
            printf("\t%s \n", $f->nodeName);
        }

        if ($argt["--showstruct"]["set"])
          printf("structure, before:\n...\n%s\n...\n", $domstruct->saveXML($xp->query('/record/description')->item(0)));

        if (is_array($fields))
        {
          foreach ($fields as $f)
          {
            $fok = true;
            $ff = $tf = null;
            if (!($ff = @$xp->query('/record/description/' . $f['from'])))
            {
              echo("ERROR : bad xml fieldname '" . $f['from'] . "'\n");
              $error = true;
              $fok = false;
            }
            if ($f['to'] && !($tf = @$xp->query('/record/description/' . $f['to'])))
            {
              echo("ERROR : bad xml fieldname '" . $f['to'] . "'\n");
              $error = true;
              $fok = false;
            }
            if ($fok)
            {
              if (in_array($f['from'], $tfields))
              {
                if ($f['to'])
                {
                  if ($tf->length == 0)
                  {
                    $oldf = $ff->item(0);
                    $newf = $domstruct->createElement($f['to']);
                    foreach ($oldf->attributes as $atn => $atv)
                    {
                      $newf->setAttribute($atn, $atv->value);
                    }
                    $oldf->parentNode->replaceChild($newf, $oldf);
                  }
                  else
                  {
                    echo("WARNING : field '" . $f['to'] . "' exists into structure, will be replace by '" . $f['from'] . "\n");
                    foreach ($tf as $n)
                      $n->parentNode->removeChild($n);
                  }
                }
                else
                {
                  foreach ($ff as $n)
                    $n->parentNode->removeChild($n);
                }
              }
              else
              {
                echo("WARNING : unknown field '" . $f['from'] . "' in structure\n");
              }
            }
          }
        }
        if ($argt["--showstruct"]["set"])
          printf("structure, after:\n...\n%s\n...\n", $domstruct->saveXML($xp->query('/record/description')->item(0)));
      }
      else
      {
        echo("ERROR : sql reading structure\n");
        $error = true;
      }
    }
    catch (Excpetion $e)
    {
      echo("ERROR accessing database\n");
      $error = true;
    }
  }
  else
  {
    echo("ERROR : unknown sbas_id " . $sbas_id . "\n");
    $error = true;
  }
}
else
{
  if ($argt["--help"]["set"])
  {
    print("BASES :\n");
    foreach ($allbas as $bas)
      printf("%5d : %s @ %s:%s\n", $bas["sbas_id"], $bas["dbname"], $bas["host"], $bas["port"]);
  }
}


if ($error || $argt["--showstruct"]["set"])
{
  flush();
  die();
}


if (!$argt['--field']['set'])
{
  print("ERROR : missing option 'field'\n");
  $error = true;
}




if ($domstruct instanceof DOMDocument)
{
  $databox->saveStructure($domstruct);
}

$dom = new DOMDocument();
$dom->formatOutput = true;
$dom->preserveWhiteSpace = false;

$recChanged = 0;

$sql = 'SELECT record_id, xml FROM record ORDER BY record_id DESC';
$connbas = $databox->get_connection();

$stmt = $connbas->prepare($sql);
$stmt->execute();
$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

foreach ($rs as $row)
{
  printf("%d \r", $row['record_id']);

  if ($dom->loadXML($row['xml']))
  {
    $oldxml = $dom->saveXML();

    $xp = new DOMXPath($dom);
    foreach ($fields as $f)
    {
      if (($tn = @$xp->query('/record/description/' . $f['from'])))
      {
        foreach ($tn as $oldn)
        {
          if ($f['to'])
          {
            $newn = $dom->createElement($f['to']);
            foreach ($oldn->childNodes as $n)
              $newn->appendChild($n);
            $oldn->parentNode->replaceChild($newn, $oldn);
          }
          else
          {
            $oldn->parentNode->removeChild($oldn);
          }
        }
      }
    }

    $newxml = $dom->saveXML();

    if ($newxml != $oldxml)
    {
      // printf("apres :\n%s\n", $dom->saveXML());

      $sql = 'UPDATE record SET xml=:xml, moddate=NOW()
              WHERE record_id = :record_id';
      $stmt = $connbas->prepare($sql);
      $stmt->execute(array(':xml' => $newxml, ':record_id' => $row['record_id']));
      $stmt->closeCursor();
      $recChanged++;
    }
  }
  else
  {
    printf("ERR (rid=%s) : bad xml \n", $row['record_id']);
  }
}

if ($recChanged > 0)
  printf("%s record(s) changed, please reindex database\n", $recChanged);
else
  printf("no record(s) changed\n");

