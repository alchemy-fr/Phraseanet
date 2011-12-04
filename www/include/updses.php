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
$session->close_storage();
$ret = array('status' => 'unknown', 'message' => false);

$request = http_request::getInstance();
$parm = $request->get_parms('usr', 'app');

if ($session->is_authenticated())
{
  $usr_id = $session->get_usr_id();
  if ($usr_id != $parm['usr']) //i logged with another user
  {
    $ret['status'] = 'disconnected';
    die(p4string::jsonencode($ret));
  }
}
else
{
  $ret['status'] = 'disconnected';
  die(p4string::jsonencode($ret));
}

try
{
  $conn = $appbox->get_connection();
}
catch (Exception $e)
{
  return p4string::jsonencode($ret);
}

$ret['apps'] = 1;

$session->set_event_module($parm['app'], true);

$ret['status'] = 'ok';
$ret['notifications'] = false;

$evt_mngr = eventsmanager_broker::getInstance($appbox);
$notif = $evt_mngr->get_notifications();

$browser = Browser::getInstance();

$twig = new supertwig();
$ret['notifications'] = $twig->render('prod/notifications.twig', array('notifications' => $notif));

$ret['changed'] = array();

$baskets = basketCollection::get_updated_baskets();
foreach ($baskets as $basket)
{
  $ret['changed'][] = $basket->get_ssel_id();
}


if (in_array($session->get_session_prefs('message'), array('1', null)))
{
  $registry = $appbox->get_registry();
  if ($registry->get('GV_maintenance'))
  {

    $ret['message'] .= '<div>' . _('The application is going down for maintenance, please logout.') . '</div>';
  }

  if ($registry->get('GV_message_on'))
  {

    $ret['message'] .= '<div>' . strip_tags($registry->get('GV_message')) . '</div>';
  }
}

echo p4string::jsonencode($ret);

