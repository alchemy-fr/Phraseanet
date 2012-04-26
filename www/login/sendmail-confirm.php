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

$request = http_request::getInstance();
$parm = $request->get_parms('usr_id');
$appbox = appbox::get_instance($Core);

$confirm = '';
try {
    $user = User_Adapter::getInstance($parm['usr_id'], $appbox);
    $usr_id = $user->get_id();
    $email = $user->get_email();

    if (mail::mail_confirmation($email, $usr_id) === true)
        $confirm = 'mail-sent';
} catch (Exception $e) {

}
return phrasea::redirect('/login/index.php?confirm=' . $confirm);
