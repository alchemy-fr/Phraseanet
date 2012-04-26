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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";

$appbox = appbox::get_instance($Core);
$request = http_request::getInstance();
$parm = $request->get_parms('code');

try {
    $datas = random::helloToken($parm['code']);
} catch (Exception_NotFound $e) {
    return phrasea::redirect('/login/?redirect=/prod&error=TokenNotFound');
}

$usr_id = $datas['usr_id'];

$user = User_Adapter::getInstance($usr_id, $appbox);

if ( ! $user->get_mail_locked()) {
    return phrasea::redirect('/login?redirect=prod&confirm=already');
}
$user->set_mail_locked(false);
random::removeToken($parm['code']);

if (PHPMailer::ValidateAddress($user->get_email())) {
    if (count($user->ACL()->get_granted_base()) > 0) {
        mail::mail_confirm_registered($user->get_email());
    }
    $user->set_mail_locked(false);
    random::removeToken($parm['code']);

    if (PHPMailer::ValidateAddress($user->get_email())) {
        $appbox_register = new appbox_register($appbox);
        $list = $appbox_register->get_collection_awaiting_for_user($user);
        $others = '';
        foreach ($list as $collection) {
            $others .= '<li>' . $collection->get_name() . "</li>\n";
        }

        mail::mail_confirm_unregistered($user->get_email(), $others);
    }
}

return phrasea::redirect('/login?redirect=/prod&confirm=ok');
