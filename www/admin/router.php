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
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";

$request = http_request::getInstance();
$parm = $request->get_parms('session');

if ($parm["session"]) {
    session_id($parm["session"]);
}

$app = require __DIR__ . "/../../lib/Alchemy/Phrasea/Application/Admin.php";

$app->run();


