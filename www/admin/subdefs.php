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
require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance();
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms("p0");

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
if (!$user->ACL()->has_right_on_sbas($parm['p0'], 'bas_modify_struct'))
{
  phrasea::headers(403);
}

try
{
  $databox = databox::get_instance((int) $parm['p0']);

  $controller = new Controller_Admin_Subdefs($request, $databox);
  $controller->render();
}
catch (Exception $e)
{
  phrasea::headers(400);
}


