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
require_once dirname(dirname(dirname(__FILE__))) . "/lib/bootstrap.php";

$request = http_request::getInstance();
$parm = $request->get_parms('cls', 'taskid');

$cls = 'task_period_' . $parm['cls'];
$registry = registry::get_instance();
$tskfile = $registry->get('GV_RootPath') . 'lib/classes/task/period/' . $parm['cls'] . '.class.php';

require_once $tskfile;

// $ztask = new $cls($parm['taskid']);

echo $ztask->facility();
