<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */


set_time_limit(0);
session_write_close();
ignore_user_abort(true);

$Core = require_once __DIR__ . '/../../lib/bootstrap.php';
$Request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

$registry = $Core->getRegistry();

$gatekeeper = gatekeeper::getInstance($Core);
$gatekeeper->require_session();

$events_mngr = $Core['events-manager'];

$user = $Core->getAuthenticatedUser();

$from = array('name'  => $user->get_display_name(), 'email' => $user->get_email());

$titre = $Request->get("type") == "title" ? : false;

$exportname = "Export_" . date("Y-n-d") . '_' . mt_rand(100, 999);

if ($Request->get("ssttid", "") != "") {
    $em = $Core->getEntityManager();
    $repository = $em->getRepository('\Entities\Basket');

    /* @var $repository \Repositories\BasketRepository */
    $Basket = $repository->findUserBasket($Request->get('ssttid'), $Core->getAuthenticatedUser(), false);

    $exportname = str_replace(' ', '_', $Basket->getName()) . "_" . date("Y-n-d");
}

$download = new set_export($Request->get('lst', ''), $Request->get('ssttid', ''));

$list = $download->prepare_export($Request->get('obj'), $titre, $Request->get('businessfields'));
$list['export_name'] = $exportname . '.zip';
$list['email'] = $Request->get("destmail", "");

$endate_obj = new DateTime('+1 day');
$endDate = $endate_obj;

$token = random::getUrlToken(\random::TYPE_EMAIL, false, $endDate, serialize($list));

//GET EMAILS

$dest = array();

$separator = preg_split('//', ' ;,', -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
$separator = '/\\' . implode('|\\', $separator) . '/';
$mails = preg_split($separator, $Request->get("destmail", ''));

foreach ($mails as $email) {
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $dest[] = $email;
    } else {
        $params = array(
            'usr_id' => $Core->getAuthenticatedUser()->get_id()
            , 'lst'    => $Request->get('lst', '')
            , 'ssttid' => $Request->get('ssttid')
            , 'dest'   => $email
            , 'reason' => \eventsmanager_notify_downloadmailfail::MAIL_NO_VALID
        );

        $events_mngr->trigger('__EXPORT_MAIL_FAIL__', $params);
    }
}

//got some emails
if (count($dest) > 0 && $token) {
    $reading_confirm_to = false;

    if ($Request->get('reading_confirm') == '1') {
        $reading_confirm_to = $user->get_email();
    }

    //BUILDING ZIP

    $zipFile = $registry->get('GV_RootPath') . 'tmp/download/' . $token . '.zip';
    set_export::build_zip($token, $list, $zipFile);

    $res = $dest;

    $url = $registry->get('GV_ServerName') . 'mail-export/' . $token . '/';

    foreach ($dest as $key => $email) {
        if (($result = mail::send_documents(trim($email), $url, $from, $endate_obj, $Request->get("textmail"), $reading_confirm_to)) === true) {
            unset($res[$key]);
        }
    }

    //some email fails
    if (count($res) > 0) {
        foreach ($res as $email) {
            $params = array(
                'usr_id' => $Core->getAuthenticatedUser()->get_id()
                , 'lst'    => $Request->get('lst')
                , 'ssttid' => $Request->get('ssttid')
                , 'dest'   => $email
                , 'reason' => \eventsmanager_notify_downloadmailfail::MAIL_FAIL
            );

            $events_mngr->trigger('__EXPORT_MAIL_FAIL__', $params);
        }
    }
} elseif ( ! $token && count($dest) > 0) {
    foreach ($res as $email) {
        $params = array(
            'usr_id' => $Core->getAuthenticatedUser()->get_id()
            , 'lst'    => $Request->get('lst')
            , 'ssttid' => $Request->get('ssttid')
            , 'dest'   => $email
            , 'reason' => 0
        );

        $events_mngr->trigger('__EXPORT_MAIL_FAIL__', $params);
    }
}




