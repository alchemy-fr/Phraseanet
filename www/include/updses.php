<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once __DIR__ . "/../../lib/bootstrap.php";
$app = new Application();
$ret = array('status'  => 'unknown', 'message' => false);

$request = http_request::getInstance();
$parm = $request->get_parms('usr', 'app');

if ($app->isAuthenticated()) {
    $usr_id = $app['phraseanet.user']->get_id();
    if ($usr_id != $parm['usr']) { //i logged with another user
        $ret['status'] = 'disconnected';
        die(p4string::jsonencode($ret));
    }
} else {
    $ret['status'] = 'disconnected';
    die(p4string::jsonencode($ret));
}

$user = $app['phraseanet.user'];

try {
    $conn = $app['phraseanet.appbox']->get_connection();
} catch (Exception $e) {
    return p4string::jsonencode($ret);
}

$ret['apps'] = 1;


$session = $app['EM']->find('Entities\Session', $app['session']->get('session_id'));

if (!$session->hasModuleId($parm['app'])) {
    $module = new \Entities\SessionModule();
    $module->setModuleId($parm['app']);
    $module->setSession($session);
    $app['EM']->persist($module);
    $app['EM']->persist($session);
}

$ret['status'] = 'ok';
$ret['notifications'] = false;

$evt_mngr = $app['events-manager'];
$notif = $evt_mngr->get_notifications();

$browser = Browser::getInstance();

$ret['notifications'] = $app['twig']->render('prod/notifications.html.twig', array('notifications' => $notif));

$ret['changed'] = array();

$repository = $app['EM']->getRepository('\Entities\Basket');

/* @var $repository \Repositories\BasketRepository */
$baskets = $repository->findUnreadActiveByUser($user);

foreach ($baskets as $basket) {
    $ret['changed'][] = $basket->getId();
}


if (in_array($app['session']->get('message'), array('1', null))) {
    if ($app['phraseanet.registry']->get('GV_maintenance')) {

        $ret['message'] .= '<div>' . _('The application is going down for maintenance, please logout.') . '</div>';
    }

    if ($app['phraseanet.registry']->get('GV_message_on')) {

        $ret['message'] .= '<div>' . strip_tags($app['phraseanet.registry']->get('GV_message')) . '</div>';
    }
}

echo p4string::jsonencode($ret);

