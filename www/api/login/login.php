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
require_once __DIR__ . "/../../../lib/bootstrap.php";


//SPECIAL ZINO
ini_set('display_errors', 'off');
ini_set('display_startup_errors', 'off');
ini_set('log_errors', 'off');
//SPECIAL ZINO
// -------------- api_utils will set vars :
//$request = http_request::getInstance();
//$parm = $request->get_parms('p', 'ses_id', 'usr_id', 'debug');
// $sxParms = simplexml_load_string($parm['p']);
// $dom : domdocument for result
// $result : documentElement for result

$registry = registry::get_instance();
require($registry->get('GV_RootPath') . 'www/api/api_utils.php');
?>
