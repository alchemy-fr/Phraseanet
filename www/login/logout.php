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
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";


$request = http_request::getInstance();
$parm = $request->get_parms("app");

try
{
  $appbox = appbox::get_instance();
  $session = $appbox->get_session();

  $session->logout();
  $session->remove_cookies();

}
catch (Exception $e)
{

}

return phrasea::redirect("/login/?redirect=/" . $parm["app"]."&logged_out=user");
