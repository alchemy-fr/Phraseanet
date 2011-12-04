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


$request = http_request::getInstance();
$parm = $request->get_parms('p', 'ses_id', 'usr_id', 'debug');

if (!$parm['debug'])
{
  phrasea::headers(200, true, 'text/xml', 'UTF-8', false);
}



$sxParms = simplexml_load_string($parm['p']);



$action = (string) $sxParms['action'];

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$result = $dom->appendChild($dom->createElement('result'));
$result->setAttribute('action', $action);

$status = 'OK';

$f = '_' . mb_strtolower($action) . '.php';
if (file_exists($f))
{
  include($f);
}
else
{
  err('bad action');
}

$result->appendChild($dom->createElement('status'))->appendChild($dom->createTextNode($status));

if ($parm['debug'])
  echo('<pre>' . htmlentities($dom->saveXML()) . '</pre>');
else
  echo $dom->saveXML();

function err($msg)
{
  global $dom, $result, $status;
  $result->appendChild($dom->createElement('err_msg'))->appendChild($dom->createTextNode($msg));
  $status = 'ERR';
}
