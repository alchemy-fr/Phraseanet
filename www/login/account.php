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
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";

$appbox = appbox::get_instance($Core);

require_once($appbox->get_registry()->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php');

$request = http_request::getInstance();
$parm = $request->get_parms("form_gender", "form_lastname", "form_firstname", "form_job", "form_company"
    , "form_function", "form_activity", "form_phone", "form_fax", "form_address", "form_zip", "form_geonameid"
    , "form_destFTP", "form_defaultdataFTP", "form_prefixFTPfolder", "notice", "form_bases", "mail_notifications", "request_notifications", 'demand', 'notifications'
    , "form_activeFTP", "form_addrFTP", "form_loginFTP", "form_pwdFTP", "form_passifFTP", "form_retryFTP");

$lng = Session_Handler::get_locale();



$user = $Core->getAuthenticatedUser();
$usr_id = $user->get_id();
$gatekeeper = gatekeeper::getInstance($Core);
$gatekeeper->require_session();

if ($user->is_guest()) {
    phrasea::headers(403);
}
phrasea::headers();


appbox_register::clean_old_requests($appbox);

if ($request->has_post_datas()) {
    $accountFields = array(
        'form_gender',
        'form_firstname',
        'form_lastname',
        'form_address',
        'form_zip',
        'form_phone',
        'form_fax',
        'form_function',
        'form_company',
        'form_activity',
        'form_geonameid',
        'form_addrFTP',
        'form_loginFTP',
        'form_pwdFTP',
        'form_destFTP',
        'form_prefixFTPfolder'
    );

    $demandFields = array(
        'demand'
    );

    $parm['notice'] = 'account-update-bad';

    if (count(array_diff($demandFields, array_keys($request->get_post_datas()))) == 0) {
        $register = new appbox_register($appbox);

        foreach ($parm["demand"] as $unebase) {
            try {
                $register->add_request($user, collection::get_from_base_id($unebase));
                $parm['notice'] = 'demand-ok';
            } catch (Exception $e) {

            }
        }
    }

    if (count(array_diff($accountFields, array_keys($request->get_post_datas()))) == 0) {

        $defaultDatas = 0;
        if ($parm["form_defaultdataFTP"]) {
            if (in_array('document', $parm["form_defaultdataFTP"]))
                $defaultDatas += 4;
            if (in_array('preview', $parm["form_defaultdataFTP"]))
                $defaultDatas += 2;
            if (in_array('caption', $parm["form_defaultdataFTP"]))
                $defaultDatas += 1;
        }
        try {
            $appbox->get_connection()->beginTransaction();
            $user->set_gender($parm["form_gender"])
                ->set_firstname($parm["form_firstname"])
                ->set_lastname($parm["form_lastname"])
                ->set_address($parm["form_address"])
                ->set_zip($parm["form_zip"])
                ->set_tel($parm["form_phone"])
                ->set_fax($parm["form_fax"])
                ->set_job($parm["form_activity"])
                ->set_company($parm["form_company"])
                ->set_position($parm["form_function"])
                ->set_geonameid($parm["form_geonameid"])
                ->set_mail_notifications(($parm["mail_notifications"] == '1'))
                ->set_activeftp($parm["form_activeFTP"])
                ->set_ftp_address($parm["form_addrFTP"])
                ->set_ftp_login($parm["form_loginFTP"])
                ->set_ftp_password($parm["form_pwdFTP"])
                ->set_ftp_passif($parm["form_passifFTP"])
                ->set_ftp_dir($parm["form_destFTP"])
                ->set_ftp_dir_prefix($parm["form_prefixFTPfolder"])
                ->set_defaultftpdatas($defaultDatas);

            $appbox->get_connection()->commit();

            $parm['notice'] = 'account-update-ok';
        } catch (Exception $e) {
            $appbox->get_connection()->rollBack();
        }
    }
}

if ($request->has_post_datas()) {
    $evt_mngr = eventsmanager_broker::getInstance($appbox, $Core);
    $notifications = $evt_mngr->list_notifications_available($appbox->get_session()->get_usr_id());

    $datas = array();

    foreach ($notifications as $notification => $nots) {
        foreach ($nots as $notification) {
            $current_notif = $user->getPrefs('notification_' . $notification['id']);

            if ( ! is_null($parm['notifications']) && isset($parm['notifications'][$notification['id']]))
                $datas[$notification['id']] = '1';
            else
                $datas[$notification['id']] = '0';
        }
    }

    foreach ($datas as $k => $v) {
        $user->setPrefs('notification_' . $k, $v);
    }
}

$geonames = new geonames();
$user = User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);

$notice = '';
if ( ! is_null($parm['notice'])) {
    switch ($parm['notice']) {
        case 'password-update-ok':
            $notice = _('login::notification: Mise a jour du mot de passe avec succes');
            break;
        case 'account-update-ok':
            $notice = _('login::notification: Changements enregistres');
            break;
        case 'account-update-bad':
            $notice = _('forms::erreurs lors de l\'enregistrement des modifications');
            break;
        case 'demand-ok':
            $notice = _('login::notification: Vos demandes ont ete prises en compte');
            break;
    }
}

$demandes = giveMeBaseUsr($usr_id, $lng);
$evt_mngr = eventsmanager_broker::getInstance($appbox, $Core);
$notifications = $evt_mngr->list_notifications_available($appbox->get_session()->get_usr_id());

$parameters = array(
    'geonames' => $geonames,
    'user' => $user,
    'notice' => $notice,
    'demandes' => $demandes,
    'evt_mngr' => $evt_mngr,
    'notifications' => $notifications,
);

$Core['Twig']->display('user/account.html.twig', $parameters);

return;
?>
