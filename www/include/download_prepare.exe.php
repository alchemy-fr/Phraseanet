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
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";

$gatekeeper = gatekeeper::getInstance($Core);
$gatekeeper->require_session();

$request = http_request::getInstance();
$parm = $request->get_parms('token');

$token = (string) ($parm["token"]);
try {
    $datas = ((random::helloToken($token)));
} catch (Exception_NotFound $e) {
    die('0');
}
if ( ! is_string($datas['datas']))
    die('0');

if (($list = @unserialize($datas['datas'])) == false) {
    die('0');
}

set_time_limit(0);
session_write_close();
ignore_user_abort(true);

$registry = registry::get_instance();
$zipFile = $registry->get('GV_RootPath') . 'tmp/download/' . $datas['value'] . '.zip';
set_export::build_zip($token, $list, $zipFile);

echo '1';
