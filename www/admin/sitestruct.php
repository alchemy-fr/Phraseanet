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
$session = $appbox->get_session();
$registry = $appbox->get_registry();

$usr_id = $session->get_usr_id();

phrasea::headers();

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
if ( ! $user->is_admin()) {
    phrasea::headers(403);
}

$request = http_request::getInstance();
$parm = $request->get_parms("act", "p0", "p1", 'flush_cache', 'sudo', 'admins', 'email');

$cache_flushed = false;

if ($parm['flush_cache']) {
    $Core = \bootstrap::getCore();
    $Core['CacheService']->flushAll();
    $cache_flushed = true;
}

if ($parm['sudo']) {
    if ($parm['sudo'] == '1') {
        User_Adapter::reset_sys_admins_rights();
    }
}

if ($parm['admins']) {
    $admins = array();

    foreach ($parm['admins'] as $a) {
        if (trim($a) == '')
            continue;

        $admins[] = $a;
    }

    if ( ! in_array($session->get_usr_id(), $admins))
        $admins[] = $session->get_usr_id();

    if ($admins > 0) {
        User_Adapter::set_sys_admins($admins);
        User_Adapter::reset_sys_admins_rights();
    }
}

try {
    $engine = new searchEngine_adapter($registry);
    $search_engine_status = $engine->get_status();
} catch(Exception $e) {
    $search_engine_status = null;
}

$php_version_constraints = setup::check_php_version();
$writability_constraints = setup::check_writability($registry);
$binaries_constraints = setup::check_binaries($registry);
$php_extension_constraints = setup::check_php_extension();
$cache_constraints = setup::check_cache_server();
$phrasea_constraints = setup::check_phrasea();
$cache_opcode_constraints = setup::check_cache_opcode();
$php_configuration_constraints = setup::check_php_configuration();

$email_status = null;

if ($parm['email']) {
    if(mail::mail_test($parm['email'])) {
        $email_status = _('Mail sent');
    } else {
        $email_status = _('Could not send email');
    }
}

$parameters = array(
    'cache_flushed' => $cache_flushed,
    'admins' => User_Adapter::get_sys_admins(),
    'email_status' => $email_status,
    'search_engine_status' => $search_engine_status,
    'php_version_constraints' => $php_version_constraints,
    'writability_constraints' => $writability_constraints,
    'binaries_constraints' => $binaries_constraints,
    'php_extension_constraints' => $php_extension_constraints,
    'cache_constraints' => $cache_constraints,
    'phrasea_constraints' => $phrasea_constraints,
    'cache_opcode_constraints' => $cache_opcode_constraints,
    'php_configuration_constraints' => $php_configuration_constraints,
);

$Core['Twig']->display('admin/dashboard.html.twig', $parameters);

return;
