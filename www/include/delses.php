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
ignore_user_abort(true);
set_time_limit(0);
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance();
$session = $appbox->get_session();
$request = http_request::getInstance();
$parm = $request->get_parms("app");


if (!$session->is_authenticated())
{
  return;
}

$session->set_event_module($parm['app'], false);


