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



set_time_limit(0);
session_write_close();
ignore_user_abort(true);

require_once __DIR__ . '/../../lib/bootstrap.php';
$app = new Application();
$Request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();


$gatekeeper = gatekeeper::getInstance($app);
$gatekeeper->require_session();

$from = array('name'  => $app['phraseanet.user']->get_display_name(), 'email' => $app['phraseanet.user']->get_email());

$titre = $Request->get("type") == "title" ? : false;

$exportname = "Export_" . date("Y-n-d") . '_' . mt_rand(100, 999);

if ($Request->get("ssttid", "") != "") {
    $repository = $app['EM']->getRepository('\Entities\Basket');

    /* @var $repository \Repositories\BasketRepository */
    $Basket = $repository->findUserBasket($app, $Request->get('ssttid'), $app['phraseanet.user'], false);

    $exportname = str_replace(' ', '_', $Basket->getName()) . "_" . date("Y-n-d");
}

$download = new set_export($app, $Request->get('lst', ''), $Request->get('ssttid', ''));

$list = $download->prepare_export($app['phraseanet.user'], $app['filesystem'], $Request->get('obj'), $titre, $Request->get('businessfields'));
$list['export_name'] = $exportname . '.zip';
$list['email'] = $Request->get("destmail", "");

$endate_obj = new DateTime('+1 day');
$endDate = $endate_obj;

$token = random::getUrlToken($app, \random::TYPE_EMAIL, false, $endDate, serialize($list));

//GET EMAILS

$dest = array();

$mails = explode(';', $Request->get("destmail", ''));

foreach ($mails as $email) {
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $dest[] = $email;
    } else {
        $params = array(
            'usr_id' => $app['phraseanet.user']->get_id()
            , 'lst'    => $Request->get('lst', '')
            , 'ssttid' => $Request->get('ssttid')
            , 'dest'   => $email
            , 'reason' => \eventsmanager_notify_downloadmailfail::MAIL_NO_VALID
        );

        $app['events-manager']->trigger('__EXPORT_MAIL_FAIL__', $params);
    }
}

//got some emails
if (count($dest) > 0 && $token) {
    $reading_confirm_to = false;

    if ($Request->get('reading_confirm') == '1') {
        $reading_confirm_to = $app['phraseanet.user']->get_email();
    }

    //BUILDING ZIP

    $zipFile = $app['phraseanet.registry']->get('GV_RootPath') . 'tmp/download/' . $token . '.zip';
    set_export::build_zip(new Filesystem(), $token, $list, $zipFile);

    $res = $dest;

    $url = $app['phraseanet.registry']->get('GV_ServerName') . 'mail-export/' . $token . '/';

    foreach ($dest as $key => $email) {
        if (($result = mail::send_documents($app, trim($email), $url, $from, $endate_obj, $Request->get("textmail"), $reading_confirm_to)) === true) {
            unset($res[$key]);
        }
    }

    //some email fails
    if (count($res) > 0) {
        foreach ($res as $email) {
            $params = array(
                'usr_id' => $app['phraseanet.user']->get_id()
                , 'lst'    => $Request->get('lst')
                , 'ssttid' => $Request->get('ssttid')
                , 'dest'   => $email
                , 'reason' => \eventsmanager_notify_downloadmailfail::MAIL_FAIL
            );

            $app['events-manager']->trigger('__EXPORT_MAIL_FAIL__', $params);
        }
    }
} elseif ( ! $token && count($dest) > 0) {
    foreach ($res as $email) {
        $params = array(
            'usr_id' => $app['phraseanet.user']->get_id()
            , 'lst'    => $Request->get('lst')
            , 'ssttid' => $Request->get('ssttid')
            , 'dest'   => $email
            , 'reason' => 0
        );

        $app['events-manager']->trigger('__EXPORT_MAIL_FAIL__', $params);
    }
}




