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
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance();
$session = $appbox->get_session();

$gatekeeper = gatekeeper::getInstance();
$gatekeeper->require_session();



$request = http_request::getInstance();
$parm = $request->get_parms("lst", "obj", "ssttid", "type");

$download = new set_export($parm['lst'], $parm['ssttid']);

if ($parm["type"] == "title")
  $titre = true;
else
  $titre=false;

$list = $download->prepare_export($parm['obj'], $titre);

$exportname = "Export_" . date("Y-n-d") . '_' . mt_rand(100, 999);

if ($parm["ssttid"] != "")
{
  $basket = basket_adapter::getInstance($appbox, $parm['ssttid'], $session->get_usr_id());
  $exportname = str_replace(' ', '_', $basket->get_name()) . "_" . date("Y-n-d");
}

$list['export_name'] = $exportname . '.zip';

$endDate = new DateTime('+3 hours');

$url = random::getUrlToken('download', $session->get_usr_id(), $endDate, serialize($list));

if ($url)
{

  $params = array(
      'lst' => $parm['lst'],
      'downloader' => $session->get_usr_id(),
      'subdefs' => $parm['obj'],
      'from_basket' => $parm["ssttid"],
      'export_file' => $exportname
  );


  $events_mngr = eventsmanager_broker::getInstance($appbox);
  $events_mngr->trigger('__DOWNLOAD__', $params);

  return phrasea::redirect('/download/' . $url);
}
phrasea::headers(500);


