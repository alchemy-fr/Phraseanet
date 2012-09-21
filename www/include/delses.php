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
ignore_user_abort(true);
set_time_limit(0);


require_once __DIR__ . "/../../lib/bootstrap.php";
$app = new Application();
$request = http_request::getInstance();
$parm = $request->get_parms("app");


if ( ! $app->isAuthenticated()) {
    return;
}
