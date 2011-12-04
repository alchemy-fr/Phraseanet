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
//SPECIAL ZINO
ini_set('display_errors', 'off');
ini_set('display_startup_errors', 'off');
ini_set('log_errors', 'off');
//SPECIAL ZINO
$appbox = appbox::get_instance();

$login = (string) ($sxParms->login);
$pwd = (string) ($sxParms->pwd);

try
{
  $auth = new Session_Authentication_Native($appbox, $login, $pwd);
  $lm = $appbox->get_session()->signOn($auth);
}
catch (Exception $e)
{
  err($lm['error']);
}
$sessid = $appbox->get_session()->get_ses_id();
$usrid = $appbox->get_session()->get_usr_id();


$result->appendChild($dom->createElement('ses_id'))->appendChild($dom->createTextNode((string) $sessid));
$result->appendChild($dom->createElement('usr_id'))->appendChild($dom->createTextNode((string) $usrid));
$xbases = $result->appendChild($dom->createElement('bases'));
foreach ($appbox->get_databoxes() as $databox)
{
  $xbase = $xbases->appendChild($dom->createElement('base'));
  $xbase->appendChild($dom->createElement('name'))->appendChild($dom->createTextNode($databox->get_viewname()));
  $xcolls = $xbase->appendChild($dom->createElement('collections'));
  foreach ($databox->get_collections() as $collection)
  {
    $xcoll = $xcolls->appendChild($dom->createElement('collection'));
    $xcoll->setAttribute('id', (string) $collection->get_base_id());
    $xcoll->appendChild($dom->createElement('name'))->appendChild($dom->createTextNode($collection->get_name()));
  }
  $xstats = $xbase->appendChild($dom->createElement('statusbits'));

  $status = $databox->get_statusbits();

  foreach ($status as $bit => $datas)
  {
    $xstat = $xstats->appendChild($dom->createElement('statusbit'));
    $xstat->setAttribute('name', $datas['name']);
    $xstat->setAttribute('index', $bit);
    $xstat0 = $xstat->appendChild($dom->createElement('label_0'))->appendChild($dom->createTextNode($datas['labeloff']));
    $xstat1 = $xstat->appendChild($dom->createElement('label_1'))->appendChild($dom->createTextNode($datas['labelon']));
  }
}
